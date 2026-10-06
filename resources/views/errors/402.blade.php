@extends('errors.layout')

@section('title', __('Payment Required'))
@section('code', '402')
@section('status_badge', app()->getLocale() === 'ar' ? 'تجديد الاشتراك' : 'SUBSCRIPTION REQUIRED')

@section('gym_headline', app()->getLocale() === 'ar' ? 'تجديد اشتراك الصالة مطلوب!' : 'Gym Membership Renewal Required!')
@section('gym_subheadline', app()->getLocale() === 'ar' 
    ? 'هذه الميزة أو الخدمة تتطلب اشتراكاً ساري المفعول أو تسوية الرسوم في مكتب استقبال النادي.' 
    : 'This feature or training program requires an active subscription or fee settlement at the gym reception desk.')

@section('gym_icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect width="20" height="14" x="2" y="5" rx="2"/>
        <line x1="2" y1="10" x2="22" y2="10"/>
        <circle cx="7" cy="15" r="1"/>
        <circle cx="11" cy="15" r="1"/>
    </svg>
@endsection

@section('message', (isset($exception) && filled($exception->getMessage())) ? $exception->getMessage() : __('Payment Required'))
