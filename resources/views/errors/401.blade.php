@extends('errors.layout')

@section('title', __('Unauthorized'))
@section('code', '401')
@section('status_badge', app()->getLocale() === 'ar' ? 'تسجيل الدخول مطلوب' : 'MEMBER PASS REQUIRED')

@section('gym_headline', app()->getLocale() === 'ar' ? 'بطاقة العضوية مطلوبة للدخول!' : 'Member Pass Required!')
@section('gym_subheadline', app()->getLocale() === 'ar' 
    ? 'تحتاج إلى تسجيل الدخول لحساب عضويتك في صالة أمل الرياضية للوصول إلى هذا المحتوى.' 
    : 'Please sign in to your Amal Gym membership account to access this training zone or dashboard area.')

@section('gym_icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect width="20" height="14" x="2" y="5" rx="2"/>
        <line x1="2" y1="10" x2="22" y2="10"/>
    </svg>
@endsection

@section('message', (isset($exception) && filled($exception->getMessage())) ? $exception->getMessage() : __('Unauthorized'))
