<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class FormValidator
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $arrRouteAction = explode('@', \Route::currentRouteAction());

        if(!empty($arrRouteAction) && count($arrRouteAction) > 1){
            $strValidator = $arrRouteAction[1].'Validator';
            if(method_exists($arrRouteAction[0], $strValidator)){
                $objValidator = Validator::make($request->all(), $arrRouteAction[0]::$strValidator($request));
                if($objValidator->fails()){
                    return response()->json($objValidator->errors(), 422);
                }
            }
        }

        return $next($request);
    }
}
