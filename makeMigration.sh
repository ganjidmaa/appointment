#!/bin/bash
php artisan migrate --domain=ds.ubisol.mn 
php artisan migrate --domain=golden-beauty.ubisol.mn 
php artisan migrate --domain=organic-care.ubisol.mn 
php artisan migrate --domain=undar-om.ubisol.mn 
php artisan migrate --domain=booking.ubisol.mn 
php artisan migrate --domain=naran.ubisol.mn
php artisan migrate --domain=cloudnine.ubisol.mn
php artisan migrate --domain=goddess.ubisol.mn

#php artisan migrate --domain=new.ubisol.mn

exec bash
