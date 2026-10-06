@extends('errors.layout')

@section('title', __('Page Not Found'))
@section('code', '404')
@section('status_badge', app()->getLocale() === 'ar' ? 'تمرين خارج المسار' : 'PAGE NOT FOUND')

@section('gym_headline', app()->getLocale() === 'ar' ? 'التمرين غير موجود في البرنامج!' : 'Exercise Not Found in Roster!')
@section('gym_subheadline', app()->getLocale() === 'ar' 
    ? 'يبدو أن الصفحة التي تبحث عنها انتقلت أو حُذفت من جدول تدريبات الصالة. تأكد من صحة الرابط أو عد إلى التدريب في الصفحة الرئيسية.' 
    : 'The page you are looking for has been moved, removed, or is temporarily not in the workout plan. Verify the link or head back to the gym lobby.')

@section('gym_icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"/>
        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        <line x1="8" y1="11" x2="14" y2="11"/>
    </svg>
@endsection

@section('message', (isset($exception) && filled($exception->getMessage())) ? $exception->getMessage() : __('Page Not Found'))
