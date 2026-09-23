@extends('layouts.print')
@section('title', 'إيصال '.$serviceRequest->order_number)

@section('content')
<div id="receipt" class="max-w-2xl mx-auto bg-white text-black p-6 rounded-lg" dir="rtl" style="font-family: 'Cairo', Tahoma, sans-serif;">
    {{-- رأس الإيصال --}}
    <div class="text-center border-b-2 border-dashed border-gray-400 pb-4 mb-4">
        <div class="flex items-center justify-center gap-3 mb-2">
            <img src="{{ asset('logo.jpg') }}" alt="لوجو" class="h-14 w-14 rounded-lg object-cover">
            <div class="text-right">
                <div class="text-xl font-extrabold">{{ $siteSettings->receipt_subtitle }}</div>
                <div class="text-[11px] text-gray-600">{{ $siteSettings->address }} — <span dir="ltr">{{ $siteSettings->phone }}</span></div>
            </div>
        </div>
        <div class="text-lg font-extrabold">{{ $siteSettings->receipt_title }}</div>
    </div>

    {{-- بيانات الطلب --}}
    <table class="w-full text-sm">
        <tbody>
            <tr>
                <td class="py-1.5 text-gray-600 font-bold w-32">رقم الإيصال</td>
                <td class="py-1.5 font-extrabold" dir="ltr">{{ $serviceRequest->order_number }}</td>
            </tr>
            <tr>
                <td class="py-1.5 text-gray-600 font-bold">التاريخ</td>
                <td class="py-1.5">{{ dt($serviceRequest->created_at, true) }}</td>
            </tr>
            <tr class="border-t border-dashed border-gray-300">
                <td class="py-1.5 text-gray-600 font-bold">اسم العميل</td>
                <td class="py-1.5 font-bold">{{ $serviceRequest->customer->name }}</td>
            </tr>
            <tr>
                <td class="py-1.5 text-gray-600 font-bold">الهاتف</td>
                <td class="py-1.5" dir="ltr">{{ $serviceRequest->phone }}</td>
            </tr>
            <tr>
                <td class="py-1.5 text-gray-600 font-bold">العنوان</td>
                <td class="py-1.5">{{ $serviceRequest->areaLabel() }} — {{ $serviceRequest->address }}</td>
            </tr>
            <tr class="border-t border-dashed border-gray-300">
                <td class="py-1.5 text-gray-600 font-bold">نوع الجهاز</td>
                <td class="py-1.5 font-bold">{{ $serviceRequest->device_type }}{{ $serviceRequest->brand ? ' — '.$serviceRequest->brand : '' }}</td>
            </tr>
            <tr>
                <td class="py-1.5 text-gray-600 font-bold">العطل المبلغ عنه</td>
                <td class="py-1.5">{{ $serviceRequest->issue_description }}</td>
            </tr>
            @if ($serviceRequest->department)
                <tr>
                    <td class="py-1.5 text-gray-600 font-bold">القسم المسؤول</td>
                    <td class="py-1.5">{{ $serviceRequest->department->name }}</td>
                </tr>
            @endif
            <tr>
                <td class="py-1.5 text-gray-600 font-bold">وضع الطلب</td>
                <td class="py-1.5 font-bold">{{ $serviceRequest->urgencyLabel() }} (مهلة {{ $serviceRequest->slaHours() }} ساعة)</td>
            </tr>
            <tr>
                <td class="py-1.5 text-gray-600 font-bold">ميعاد الدخول</td>
                <td class="py-1.5" dir="ltr">{{ $serviceRequest->entered_at?->format('Y/m/d H:i') ?? '—' }}</td>
            </tr>
            <tr>
                <td class="py-1.5 text-gray-600 font-bold">ميعاد الخروج المتوقع</td>
                <td class="py-1.5" dir="ltr">{{ $serviceRequest->slaDeadline()->format('Y/m/d H:i') }}</td>
            </tr>
            @if ($serviceRequest->return_count > 0)
                <tr>
                    <td class="py-1.5 text-gray-600 font-bold">مرتجعات سابقة</td>
                    <td class="py-1.5 font-bold">{{ $serviceRequest->return_count }} مرتجع</td>
                </tr>
            @endif
            <tr>
                <td class="py-1.5 text-gray-600 font-bold">الميعاد المطلوب</td>
                <td class="py-1.5">{{ dt($serviceRequest->preferred_date) }} — {{ $serviceRequest->timeSlotLabel() }}</td>
            </tr>
            @if ($serviceRequest->price !== null && $serviceRequest->status === 'COMPLETED')
                <tr class="border-t border-dashed border-gray-300">
                    <td class="py-1.5 text-gray-600 font-bold">أجر الصيانة (المحصّل كاملاً)</td>
                    <td class="py-1.5 font-extrabold">{{ money($serviceRequest->price) }}</td>
                </tr>
                @if ($serviceRequest->payment_method)
                    <tr>
                        <td class="py-1.5 text-gray-600 font-bold">طريقة الدفع</td>
                        <td class="py-1.5 font-extrabold">{{ $serviceRequest->paymentLabel() }}</td>
                    </tr>
                @endif
            @endif
            @if ($serviceRequest->hasWarranty())
                <tr class="border-t border-dashed border-gray-300">
                    <td class="py-1.5 text-gray-600 font-bold">الضمان</td>
                    <td class="py-1.5 font-bold">{{ $serviceRequest->warranty_months }} شهر — حتى {{ dt($serviceRequest->warranty_end_date) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- قطع الغيار المستخدمة + الإجمالي الكلي — زي فاتورة الأصل بالظبط --}}
    @php $partsTotal = (float) $serviceRequest->partsTotal(); @endphp
    @if ($serviceRequest->usedParts->isNotEmpty())
        <div class="mt-4 pt-4 border-t-2 border-dashed border-gray-400">
            <div class="text-sm font-extrabold mb-2">قطع الغيار المستخدمة</div>
            <table class="w-full text-[12px]">
                <thead>
                    <tr class="border-b-2 border-emerald-700">
                        <th class="py-2 px-1 text-right">القطعة</th>
                        <th class="py-2 px-1 text-center">الكمية</th>
                        <th class="py-2 px-1 text-center">سعر الوحدة</th>
                        <th class="py-2 px-1 text-left">الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($serviceRequest->usedParts as $part)
                        <tr class="border-b border-gray-200">
                            <td class="py-1.5 px-1">{{ $part->name() }}{{ $part->inventoryItem?->brand ? ' ('.$part->inventoryItem->brand.')' : '' }}</td>
                            <td class="py-1.5 px-1 text-center">{{ $part->quantity }} {{ $part->inventoryItem?->unit ?: 'قطعة' }}</td>
                            <td class="py-1.5 px-1 text-center">{{ money($part->unit_price) }}</td>
                            <td class="py-1.5 px-1 text-left font-semibold">{{ money($part->quantity * $part->unit_price) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-emerald-700">
                        <td colspan="3" class="py-2 px-1 text-right font-bold">إجمالي قطع الغيار:</td>
                        <td class="py-2 px-1 text-left font-extrabold text-emerald-700 text-base">{{ money($partsTotal) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    {{-- الإجمالي الكلي = سعر الصيانة + قطع الغيار --}}
    @php
        $servicePrice = (float) ($serviceRequest->price ?? 0);
        $grandTotal = $servicePrice + $partsTotal;
    @endphp
    @if ($servicePrice > 0 || $partsTotal > 0)
        <div class="mt-4 bg-green-50 border-2 border-emerald-700 rounded-xl p-4">
            @if ($servicePrice > 0)
                <div class="flex justify-between text-sm mb-2">
                    <span>سعر الصيانة:</span>
                    <span class="font-semibold">{{ money($servicePrice) }}</span>
                </div>
            @endif
            @if ($partsTotal > 0)
                <div class="flex justify-between text-sm mb-2">
                    <span>قطع الغيار:</span>
                    <span class="font-semibold">{{ money($partsTotal) }}</span>
                </div>
            @endif
            <div class="border-t-2 border-emerald-700 mt-2 pt-2 flex justify-between">
                <span class="font-bold text-base">الإجمالي الكلي:</span>
                <span class="font-extrabold text-2xl text-emerald-700">{{ money($grandTotal) }}</span>
            </div>
            @if ($serviceRequest->status === 'COMPLETED' && $serviceRequest->payment_method)
                <div class="flex justify-between text-xs text-gray-600 mt-2 pt-2 border-t border-dashed border-gray-300">
                    <span>تم التحصيل بالكامل عند التسليم</span>
                    <span class="font-bold">{{ $serviceRequest->paymentLabel() }}</span>
                </div>
            @endif
        </div>
    @endif

    {{-- ملاحظات الإيصال --}}
    <div class="mt-4 pt-4 border-t-2 border-dashed border-gray-400 text-[11px] text-gray-700 leading-relaxed">
        <p class="mb-1 font-bold">{{ $siteSettings->receipt_notes }}</p>
        <p>{{ $siteSettings->receipt_footer }}</p>
    </div>

    {{-- توقيعات --}}
    <div class="mt-6 grid grid-cols-2 gap-6 text-center text-xs">
        <div>
            <div class="border-t border-gray-500 pt-1.5 font-bold">توقيع المستلم</div>
        </div>
        <div>
            <div class="border-t border-gray-500 pt-1.5 font-bold">ختم المركز</div>
        </div>
    </div>
</div>
@endsection
