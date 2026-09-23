@extends('errors.layout')

@section('title', 'غير مسموح بالدخول')
@section('code', '403')
@section('message', 'ليكش صلاحية الوصول للصفحة دي — صلاحيات كل دور محددة في النظام. لو شايف ده غلط تواصل مع الإدارة.')

@section('actions')
    <a href="javascript:history.back()" class="btn ghost">رجوع للخلف</a>
@endsection
