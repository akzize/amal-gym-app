@extends('errors.layout')

@section('title', __('Client Error'))
@section('code', (isset($exception) && method_exists($exception, 'getStatusCode')) ? $exception->getStatusCode() : '4XX')
@section('status_badge', app()->getLocale() === 'ar' ? 'خطأ في الطلب' : 'CLIENT ERROR')

@section('gym_headline', app()->getLocale() === 'ar' ? 'تعذر إكمال طلبك في الصالة!' : 'Workout Request Could Not Complete!')
@section('gym_subheadline', app()->getLocale() === 'ar' 
    ? 'تعذر على النظام معالجة هذا الإجراء. يرجى التحقق من الرابط أو المحاولة لاحقاً.' 
    : 'The gym system was unable to complete your request. Please check the details and try again.')

@section('message', (isset($exception) && filled($exception->getMessage())) ? $exception->getMessage() : __('An error occurred while processing your request.'))
