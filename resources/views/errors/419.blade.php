@extends('errors.layout')

@section('title', 'انتهت مدة الجلسة')
@section('code', '419')
@section('message', 'جلستك انتهت للأمان — سجل دخولك تاني وهتكمل من حيث وقفت.')

@section('actions')
    <a href="{{ url('/login') }}" class="btn ghost">تسجيل الدخول</a>
@endsection
