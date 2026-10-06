@extends('errors.layout')

@section('title', __('Service Unavailable'))
@section('code', '503')
@section('status_badge', app()->getLocale() === 'ar' ? 'صيانة دورية' : 'GYM MAINTENANCE')

@section('gym_headline', app()->getLocale() === 'ar' ? 'الصالة الرياضية في فترة صيانة وتطوير!' : 'Gym Upgrades & Maintenance in Progress!')
@section('gym_subheadline', app()->getLocale() === 'ar' 
    ? 'نقوم حالياً بتحديث وصيانة أجهزة الصالة والأنظمة الرقمية لتقديم أفضل أداء رياضي لكم. سنعود للعمل بكامل طاقتنا في أقرب وقت.' 
    : 'We are currently tuning up gym equipment and polishing digital features to give you the ultimate workout experience. We will be back stronger in a moment.')

@section('gym_icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
    </svg>
@endsection

@section('message', (isset($exception) && filled($exception->getMessage())) ? $exception->getMessage() : __('Service Unavailable'))
