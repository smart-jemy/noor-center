@extends('errors.layout')

@section('title', 'حدث خطأ غير متوقع')
@section('code', '500')
@section('message', 'حصلت مشكلة مؤقتة في معالجة الطلب — النظام سجلها وهيكمل شغال طبيعي. جرّب تاني أو ارجع للرئيسية.')

@section('actions')
    <a href="javascript:history.back()" class="btn ghost">إعادة المحاولة</a>
@endsection
