@extends('errors.layout')

@section('title', __('Server Error'))
@section('code', '500')
@section('status_badge', app()->getLocale() === 'ar' ? 'عطل فني' : 'EQUIPMENT OVERLOAD')

@section('gym_headline', app()->getLocale() === 'ar' ? 'عطل فني مفاجئ في الأجهزة!' : 'Technical Equipment Malfunction!')
@section('gym_subheadline', app()->getLocale() === 'ar' 
    ? 'واجه خادم النادي حمولة زائدة أو عطلاً تقنياً أثناء تنفيذ طلبك. طاقمنا الفني يعمل على صيانة الخلل فوراً.' 
    : 'The gym server hit an unexpected load failure while processing your request. Our technical staff has been alerted to fix it right away.')

@section('gym_icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="m13 2-2 10h5l-4 10 2-10h-5l4-10z"/>
    </svg>
@endsection

@section('message', (isset($exception) && filled($exception->getMessage())) ? $exception->getMessage() : __('Server Error'))
