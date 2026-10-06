@extends('errors.layout')

@section('title', __('Server Error'))
@section('code', (isset($exception) && method_exists($exception, 'getStatusCode')) ? $exception->getStatusCode() : '5XX')
@section('status_badge', app()->getLocale() === 'ar' ? 'عطل غير متوقع' : 'SERVER ISSUE')

@section('gym_headline', app()->getLocale() === 'ar' ? 'واجه خادم الصالة خطأ غير متوقع!' : 'Unexpected Gym Server Error!')
@section('gym_subheadline', app()->getLocale() === 'ar' 
    ? 'حدث خطأ تقني في خادم النادي. نعتذر عن هذا الإزعاج، ويجري العمل على تفاديه.' 
    : 'An unexpected internal error occurred on the gym system. We apologize for the inconvenience and are working to resolve it.')

@section('message', (isset($exception) && filled($exception->getMessage())) ? $exception->getMessage() : __('Server Error'))
