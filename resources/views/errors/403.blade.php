@extends('errors.layout')

@section('title', __('Forbidden'))
@section('code', '403')
@section('status_badge', app()->getLocale() === 'ar' ? 'منطقة مقيدة' : 'ACCESS RESTRICTED')

@section('gym_headline', app()->getLocale() === 'ar' ? 'منطقة تدريب خاصة بالأعضاء المصرح لهم!' : 'VIP & Restricted Training Zone!')
@section('gym_subheadline', app()->getLocale() === 'ar' 
    ? 'لا تملك الصلاحيات الكافية للوصول إلى هذا القسم في الصالة الرياضية. إذا كنت تعتقد أن هذا خطأ، يرجى مراجعة إدارة النادي أو المدرب المسؤول.' 
    : 'You do not have the required clearance to access this gym section. If you believe this is a mistake, please check with gym administration or staff.')

@section('gym_icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        <circle cx="12" cy="16" r="1.5"/>
    </svg>
@endsection

@section('message', (isset($exception) && filled($exception->getMessage())) ? $exception->getMessage() : __('Forbidden'))
