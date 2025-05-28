<?php

namespace App\Http\Controllers;

use App\Models\SmsHistory;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\Settings;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Exports\GeneralReportExport;
use App\Exports\IncomeReportByUsersExport;
use App\Exports\IncomeReportByDaysExport;
use App\Exports\AttendanceReportByUsersExport;
use App\Exports\AttendanceReportByServicesExport;
use App\Exports\AttendanceReportByRushHours;
use App\Exports\CustomerReportMultiSheet;
use App\Exports\CouponCodeExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ReportsController extends Controller
{
    public function getDashboardData(Request $request) {
        Gate::authorize('view-dashboard');

        // $start_date = date("Y-01-01");
        // $end_date = date("Y-12-31");

        $start_date = date('Y-m-d 00:00:00', strtotime($request->interval[0]));
        $end_date = date('Y-m-d 23:59:59', strtotime($request->interval[1]));

        $event_colors = [
            '#FFA800' => 'booked',
            '#8950FC' => 'no_show',
            '#F64E60' => 'customer_cancelled',
            '#3A2434' => 'user_cancelled',
            '#1BC5BD' => 'completed',
        ];

        $total_income = [];
        $appointment_status_datas = [];

        $total_income = Invoice::selectRaw('SUM(paid) as income, DATE_FORMAT(created_at, "%m") as month')
            ->where('state', '!=', 'voided')
            ->whereBetween('created_at', [$start_date, $end_date])
            ->groupByRaw('MONTH(created_at)')
            ->orderBy('month', 'asc')
            ->get();

        $appointment_statuses = DB::table('events')
            ->selectRaw('count(CASE WHEN status = "cancelled" and cancellation_type = "user_request" THEN 1 END) as customer_cancelled_events,
                count(CASE WHEN status = "cancelled" and cancellation_type = "mistake" THEN 1 END) as user_cancelled_events,
                count(CASE WHEN status = "no_show" THEN 1 END) as no_showed_events,
                count(CASE WHEN status IN ("booked", "confirmed") THEN 1 END) as booked_events,
                count(CASE WHEN status NOT IN ("booked", "no_show", "cancelled", "confirmed", "time_block") THEN 1 END) as showed_events')
            ->leftJoin('appointments', 'appointments.id', 'events.appointment_id')
            ->whereBetween('events.start_time',  [$start_date, $end_date])
            ->first();

        $branches = DB::select(DB::raw('SELECT * FROM branches')->getValue(DB::connection()->getQueryGrammar()));
        $users = DB::select(DB::raw('SELECT * FROM users')->getValue(DB::connection()->getQueryGrammar()));

        $branch_appointments = DB::table('appointments')
            ->selectRaw('count(id) as total, YEAR(appointments.event_date) as myear, MONTH(appointments.event_date) as mmonth, branch_id')
            ->whereRaw('appointments.status NOT IN ("no_show", "cancelled", "time_block")')
            ->whereBetween('appointments.event_date',  [$start_date, $end_date])
            ->groupBy(DB::raw('YEAR(appointments.event_date), MONTH(appointments.event_date), appointments.branch_id'))
            ->orderBy('myear', 'asc')->orderBy('mmonth','asc')
            ->get();

        $app_data = [];
        $names = [];
        foreach ($branch_appointments as $appointment)
        {
            $names[] = $appointment->myear . '/' . $appointment->mmonth;
        }

        $names = array_unique($names);
        $names = array_values($names);

        foreach($names as $key => $name)
        {
            foreach ($branches as $branch)
            {
                $app_data[$branch->name][$key] = ['x'=>$name, 'y'=>doubleval(0)];
            }
        }


        foreach ($branch_appointments as $appointment)
        {
            $date = $appointment->myear . '/' . $appointment->mmonth;
            foreach ($branches as $branch)
            {
                foreach($names as $key => $name)
                {
                    if($name == $date)
                    {
                        if($branch->id == $appointment->branch_id)
                        {
                            $app_data[$branch->name][$key] = ['x'=>$date, 'y'=>doubleval($appointment->total)];

                        }
                    }
                }
            }
        }

        $arr = array();

        foreach ($app_data as $key=>$value) {
            $arr[] = array('name' => $key, 'type' => 'bar', 'data' => $value);
        }


        $app_data =[];
        $branch_total_income = Invoice::selectRaw('SUM(paid) as income, YEAR(invoices.created_at) as myear, MONTH(invoices.created_at) as mmonth, branch_id')
            ->leftJoin('appointments','appointments.id', 'invoices.appointment_id')
            ->where('state', '!=', 'voided')
            ->whereBetween('invoices.created_at', [$start_date, $end_date])
            ->groupBy(DB::raw('YEAR(invoices.created_at), MONTH(invoices.created_at), appointments.branch_id'))
            ->orderBy('myear', 'asc')->orderBy('mmonth','asc')
            ->get();

        foreach ($branch_total_income as $invoice)
        {
            $names[] = $invoice->myear . '/' . $invoice->mmonth;
        }

        $names = array_unique($names);
        $names = array_values($names);

        foreach($names as $key => $name)
        {
            foreach ($branches as $branch)
            {
                $app_data[$branch->name][$key] = ['x' => $name, 'y' => doubleval(0)];
            }
        }

        foreach ($branch_total_income as $invoice)
        {
            $date = $invoice->myear . '/' . $invoice->mmonth;
            foreach ($branches as $branch)
            {
                foreach($names as $key => $name)
                {
                    if($name == $date)
                    {
                        if($branch->id == $appointment->branch_id)
                        {
                            $app_data[$branch->name][$key] = ['x'=>$date, 'y'=>doubleval($invoice->income)];

                        }
                    }
                }
            }
        }

        $branch_incomes = array();

        foreach ($app_data as $key=>$value)
        {
            $branch_incomes[] = array('name' => $key, 'type' => 'bar', 'data' => $value);
        }


        $appointment_status_datas['colors'][] = array_search('customer_cancelled', $event_colors);
        $appointment_status_datas['numbers'][] = $appointment_statuses->customer_cancelled_events;
        $appointment_status_datas['labels'][] = 'Үйлчлүүлэгч цуцалсан';

        $appointment_status_datas['colors'][] = array_search('user_cancelled', $event_colors);
        $appointment_status_datas['numbers'][] = $appointment_statuses->user_cancelled_events;
        $appointment_status_datas['labels'][] = 'Ажилтан цуцалсан';

        $appointment_status_datas['colors'][] = array_search('no_show', $event_colors);
        $appointment_status_datas['numbers'][] = $appointment_statuses->no_showed_events;
        $appointment_status_datas['labels'][] = 'Ирээгүй';

        $appointment_status_datas['colors'][] = array_search('booked', $event_colors);
        $appointment_status_datas['numbers'][] = $appointment_statuses->booked_events;
        $appointment_status_datas['labels'][] = 'Ирэх ёстой';

        $appointment_status_datas['colors'][] = array_search('completed', $event_colors);
        $appointment_status_datas['numbers'][] = $appointment_statuses->showed_events;
        $appointment_status_datas['labels'][] = 'Ирсэн';
        

        $income_with_users = Invoice::selectRaw('SUM(paid) as user_income, users.firstname')
            ->leftJoin('users', 'users.id', 'invoices.user_id')
            ->where('invoices.state', '!=', 'voided')
            ->whereBetween('invoices.created_at',  [$start_date, $end_date])
            ->groupBy('invoices.user_id')
            ->orderBy('user_income', 'desc')
            ->get();

        $appointment_number_users = Event::selectRaw('COUNT(events.id) as appt_number, users.firstname, 
                YEAR(events.start_time) as myear, MONTH(events.start_time) as mmonth')
            ->leftJoin('users', 'users.id', 'events.user_id')
            ->leftJoin('appointments', 'appointments.id', 'events.appointment_id')  
            ->whereRaw('appointments.status NOT IN ("no_show", "cancelled", "time_block")')
            ->whereBetween('events.start_time', [$start_date, $end_date])
            ->groupBy(DB::raw('YEAR(events.start_time), MONTH(events.start_time), users.id'))
            ->orderBy('myear', 'asc')->orderBy('mmonth','asc')
            ->get();

        $dates = [];
        $series = [];
        $users = [];
        $appointments_by_users = [];
        $total_appt = 0;
        foreach ($appointment_number_users as $appointment)
        {
            $dates[] = $appointment->myear . '/' . $appointment->mmonth;
            $users[] = $appointment->firstname;
        }

        $dates = array_unique($dates);
        $users = array_unique($users);
        $dates = array_values($dates);
        $users = array_values($users);

        foreach($users as $key => $user) {
            if($user) {
            $series['name'] = $user;
            $datas = [];
            foreach($dates as $date_val) {
                $appointment_number = 0;

                foreach($appointment_number_users as $appointment) {
                    $date = $appointment->myear . '/' . $appointment->mmonth;
        
                    if($date_val == $date && $appointment->firstname == $user) {
                        $appointment_number = $appointment->appt_number;
                    }
                }
                $datas[] = $appointment_number;
                $total_appt += $appointment_number;
            }
            $series['data'] = $datas;
            $appointments_by_users['series'][] = $series;
        }
        }
        $appointments_by_users['resources'] = $dates;
        $appointments_by_users['totalCount'] = $total_appt;

        $data['totalIncome'] = $total_income;
        $data['appointmentStatusData'] = $appointment_status_datas;
        $data['userIncome'] = $income_with_users;
        $data['userAppointment'] = $appointments_by_users;
        $data['branchAppointments'] = $arr;
        $data['branchIncomes'] = $branch_incomes;

        $response['data'] = $data;
        $response['payload'] = ['status' => 200];

        return response($response);
    }

    public function generalReport(Request $request) {
        $file_name = 'Үйлчилгээний дэлгэрэнгүй тайлан '.date('m.d', strtotime($request->start_date)) .'-'. date('m.d', strtotime($request->end_date)).'.xlsx';
        $settings = Settings::find(1);
        Excel::store(new GeneralReportExport([$request->start_date, $request->end_date], $settings->has_service_type), $file_name, 'excels');

        $response['data'] = ['file_name' => $file_name];
        $response['payload'] = ['status' => 200];
        return response($response);
    }

    public function incomeReportByDays(Request $request) {
        $file_name = 'Орлогын тайлан - өдрөөр '.date('m.d', strtotime($request->start_date)) .'-'. date('m.d', strtotime($request->end_date)).'.xlsx';
        Excel::store(new IncomeReportByDaysExport([$request->start_date, $request->end_date]), $file_name, 'excels');

        $response['data'] = ['file_name' => $file_name];
        $response['payload'] = ['status' => 200];
        return response($response);
    }

    public function incomeReportByUsers(Request $request) {
        $file_name = 'Орлогын тайлан - ажилтан '.date('m.d', strtotime($request->start_date)) .'-'. date('m.d', strtotime($request->end_date)).'.xlsx';
        Excel::store(new IncomeReportByUsersExport([$request->start_date, $request->end_date]), $file_name, 'excels');

        $response['data'] = ['file_name' => $file_name];
        $response['payload'] = ['status' => 200];
        return response($response);
    }

    public function attendanceReportByUsers(Request $request) {
        $file_name = 'Үйлчилгээний тайлан - ажилтан '.date('m.d', strtotime($request->start_date)) .'-'. date('m.d', strtotime($request->end_date)).'.xlsx';
        Excel::store(new AttendanceReportByUsersExport([$request->start_date, $request->end_date]), $file_name, 'excels');

        $response['data'] = ['file_name' => $file_name];
        $response['payload'] = ['status' => 200];
        return response($response);
    }

    public function attendanceReportByServices(Request $request) {
        $file_name = 'Үйлчилгээний тайлан - үйлчилгээ '.date('m.d', strtotime($request->start_date)) .'-'. date('m.d', strtotime($request->end_date)).'.xlsx';
        $settings = Settings::find(1);
        Excel::store(new AttendanceReportByServicesExport([$request->start_date, $request->end_date], $settings->has_service_type), $file_name, 'excels');

        $response['data'] = ['file_name' => $file_name];
        $response['payload'] = ['status' => 200];
        return response($response);
    }

    public function couponCodeDownload(Request $request) {
        $file_name = 'coupon_code_list.xlsx';
        Excel::store(new CouponCodeExport([$request->start_date, $request->end_date]), $file_name, 'excels');

        $response['data'] = ['file_name' => $file_name];
        $response['payload'] = ['status' => 200];
        return response($response);
    }

    public function attendanceReportByRushHours(Request $request) {
        $file_name = 'Үйлчилгээний тайлан - оргил ачааллаар '.date('m.d', strtotime($request->start_date)) .'-'. date('m.d', strtotime($request->end_date)).'.xlsx';
        Excel::store(new AttendanceReportByRushHours([$request->start_date, $request->end_date]), $file_name, 'excels');

        $response['data'] = ['file_name' => $file_name];
        $response['payload'] = ['status' => 200];
        return response($response);
    }

    public function customerReportByFrequency(Request $request) {
        $file_name = 'Хэрэглэгчийн тайлан - давтамжаар '.date('m.d', strtotime($request->start_date)) .'-'. date('m.d', strtotime($request->end_date)).'.xlsx';
        $settings = Settings::find(1);
        Excel::store(new CustomerReportMultiSheet([$request->start_date, $request->end_date], $settings->has_service_type, $settings->has_branch), $file_name, 'excels');

        $response['data'] = ['file_name' => $file_name];
        $response['payload'] = ['status' => 200];
        return response($response);
    }

    public function eventDetailsReport(Request $request)
    {
        // $this->authorize('view', Event::class);

        $search = $request->search;
        $where = '1 = 1';
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d');
        $filter = '1';
        if($search) {
            $where = 'customers.firstname like "%'.$search.'%" or status like "%'.$search.'%" or start_time like "%'.$search.'%"
                or total_duration like "%'.$search.'%" or payment like "%'.$search.'%" or discount_amount like "%'.$search.'%"
                or paid like "%'.$search.'%" or left_amount like "%'.$search.'%" or events_users.user_firstnames like "%'.$search.'%" ';
        }
        if($request->filter_residue) {
            $add_condition = $filter != '1' ? $filter.' AND ' : '';
            $filter = $add_condition.$request->filter_residue == 'payment_with_residue' ? 'left_amount > 0' : '(left_amount <= 0 or left_amount is null)';
        }
        if($request->filter_status) {
            $add_condition = $filter != '1' ? $filter.' AND ' : '';
            $status = $request->filter_status == 'completed,part_paid,unpaid' ? '"completed","part_paid","unpaid"' : '"'.$request->filter_status.'"';
            $filter = $add_condition.'appointments.status IN ('.$status.')';
        }
        if($request->dates_start_date && $request->dates_end_date) {
            $start_date = date('Y-m-d', strtotime($request->dates_start_date));
            $end_date = date('Y-m-d', strtotime($request->dates_end_date));
        }

        $appointments = Appointment::selectRaw("appointments.status, customers.firstname,events_users.user_firstnames, event_number, total_duration, start_time,
                payment, discount_amount, paid, left_amount, event_date")
            ->leftJoin('customers', 'customers.id', 'appointments.customer_id')
            ->leftJoin(DB::raw('(SELECT *, (payment - discount_amount - paid) as left_amount FROM invoices
                        WHERE invoices.state != "voided") new_invoices'), 'new_invoices.appointment_id', 'appointments.id')
            ->leftJoin(DB::raw('(SELECT COUNT(id) as event_number, SUM(duration) as total_duration, MIN(start_time) as start_time, appointment_id FROM events
                GROUP BY appointment_id) grouped_events'), 'grouped_events.appointment_id', 'appointments.id')
            ->leftJoin(DB::raw('(select appointment_id, GROUP_CONCAT(users.firstname separator " ") as user_firstnames from (SELECT * FROM events 
            GROUP BY user_id, appointment_id) as events_fix
            LEFT JOIN users on users.id = events_fix.user_id
            GROUP BY appointment_id) events_users'), 'events_users.appointment_id','appointments.id')
            ->whereRaw($where)
            ->whereRaw('appointments.status NOT IN ("time_block")')
            ->whereRaw($filter)
            ->whereBetween('event_date', [$start_date, $end_date])
            ->orderBy('start_time', 'asc');

            $total_payment = 0;
            $total_paid = 0;
            foreach($appointments->get() as $appointment) {
                $total_payment += $appointment->payment;
                $total_paid += $appointment->paid;
            }

            $appointments = $appointments->paginate($request->items_per_page)->withQueryString();

            $data = [];
            $datas = [];
            foreach($appointments as $appointment) {
                $data['customer_name'] = $appointment->firstname;
                $data['status'] = $appointment->status;
                $data['start_time'] = date('Y-m-d H:i', strtotime($appointment->start_time));
                $data['duration'] = $appointment->total_duration;
                $data['payment'] = $appointment->payment;
                $data['discount'] = $appointment->discount_amount;
                $data['paid'] = $appointment->paid;
                $data['left_amount'] = $appointment->left_amount;
                $data['event_number'] = $appointment->event_number;
                $data['username'] = $appointment->user_firstnames;

                $datas[] = $data;
            }

        $status = 200;
        $payload = [
            'pagination' => $appointments,
            'status' => $status
        ];

        $master_data['events'] = $datas;
        $master_data['total_payment'] = $total_payment;
        $master_data['total_paid'] = $total_paid;

        $responce['data'] = [$master_data];
        $responce['payload'] = $payload;
        return response($responce);
    }

    public function smsHistory(Request $request){
        $search = $request->search;
        $where = '1 = 1';
        $start_date = date('Y-m-d 00:00:00');
        $end_date = date('Y-m-d 23:59:59');
        $filter = '1';
        if($search) {
            $where = 'msg like "%'.$search.'%" or status like "%'.$search.'%" or created_at like "%'.$search.'%"
                or tel like "%'.$search.'%" or type like "%'.$search.'%" or result like "%'.$search.'%" ';
        }
        if($request->filter_residue) {
            $add_condition = $filter != '1' ? $filter.' AND ' : '';
            $filter = $add_condition.$request->filter_residue == 'successful' ? 'status = 1' : 'status = 0';
        }
        if($request->filter_status) {
            $add_condition = $filter != '1' ? $filter.' AND ' : '';
            $status = $request->filter_status == 'completed,part_paid,unpaid' ? '"completed","part_paid","unpaid"' : '"'.$request->filter_status.'"';
            $filter = $add_condition.'appointments.status IN ('.$status.')';
        }
        if($request->dates_start_date && $request->dates_end_date) {
            $start_date = date('Y-m-d', strtotime($request->dates_start_date));
            $end_date = date('Y-m-d', strtotime($request->dates_end_date));
        }

        $sms_histories_query = SmsHistory::query()
            ->whereRaw($where)
            ->whereRaw($filter)
            ->whereBetween('created_at', [$start_date, $end_date])
            ->orderBy('id', 'asc');

        $sms_count = $sms_histories_query->count();
        $sms_histories = $sms_histories_query->paginate($request->items_per_page)->withQueryString();
        $success_count = $sms_histories_query->where('status', 1)->count();
        $failed_count = $sms_count - $success_count;

        $data = [];
        $datas = [];
        foreach($sms_histories as $sms_history) {
            $data['msg'] = $sms_history->msg;
            $data['status'] = $sms_history->status;
            $data['created_at'] = date('Y-m-d H:i', strtotime('+8 hours', strtotime($sms_history->created_at)));
            $data['tel'] = $sms_history->tel;
            $data['result'] = $sms_history->result;
            $data['type'] = $sms_history->type;
            $data['id'] = $sms_history->id;

            $datas[] = $data;
        }

        $status = 200;
        $payload = [
            'pagination' => $sms_histories,
            'status' => $status
        ];
        $settings = Settings::find(1);
        $sms_left = $settings->sms_limit - $settings->sms_count;
        $master_data['sms_history'] = $datas;
        $master_data['sms_left'] = $sms_left;
        $master_data['sms_count'] = $sms_count;
        $master_data['failed_count'] = $failed_count;
        $master_data['success_count'] = $success_count;

        $responce['data'] = [$master_data];
        $responce['payload'] = $payload;
        return response($responce);
    }
}
