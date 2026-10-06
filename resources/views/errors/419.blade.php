@extends('errors.layout')

@section('title', __('Page Expired'))
@section('code', '419')
@section('status_badge', app()->getLocale() === 'ar' ? 'انتهت الجلسة' : 'SESSION EXPIRED')

@section('gym_headline', app()->getLocale() === 'ar' ? 'طالت مدة الراحة وانتهت الجلسة!' : 'Workout Session Timed Out!')
@section('gym_subheadline', app()->getLocale() === 'ar' 
    ? 'انتهت صلاحية رمز الأمان (CSRF) بسبب عدم النشاط لفترة طويلة في الصالة. يرجى إعادة تحديث الصفحة ومتابعة تمرينك.' 
    : 'Your security token expired due to gym inactivity. Refresh the page or log in again to resume your fitness session.')

@section('gym_icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/>
        <polyline points="12 6 12 12 14 14"/>
    </svg>
@endsection

@section('message', (isset($exception) && filled($exception->getMessage())) ? $exception->getMessage() : __('Page Expired'))
