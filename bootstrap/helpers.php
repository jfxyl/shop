<?php
function route_class(){
    return str_replace('.', '-', Route::currentRouteName());
}

function ngrok_url($routeName,$params = []){
    if(app()->environment('local') && $url = config('app.ngrok_url')){
        return $url . route($routeName,$params,false);
    }
    return route($routeName,$params);
}

function big_number($number, $scale = 2)
{
    return new \Moontoast\Math\BigNumber($number, $scale);
}
