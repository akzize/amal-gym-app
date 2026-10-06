@extends('errors.layout')

@section('title', __('Too Many Requests'))
@section('code', '429')
@section('status_badge', app()->getLocale() === 'ar' ? 'تمهل قليلاً' : 'TOO MANY REPS')

@section('gym_headline', app()->getLocale() === 'ar' ? 'تمهل بين المجموعات والتكرارات!' : 'Pace Your Sets, Take a Rest!')
@section('gym_subheadline', app()->getLocale() === 'ar' 
    ? 'لقد قمت بإرسال طلبات سريعة وكثيرة في وقت وجيز. استرح بضع لحظات لتستعيد عافيتك ثم أعد المحاولة مجدداً.' 
    : 'You performed too many rapid-fire requests in a short workout window. Take a quick breather and try again in a moment.')

@section('gym_icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
    </svg>
@endsection

@section('message', (isset($exception) && filled($exception->getMessage())) ? $exception->getMessage() : __('Too Many Requests'))
