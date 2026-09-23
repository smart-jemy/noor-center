@extends('admin.layout')
@section('title', $user->name.' — العملاء')
@section('admin_title', $user->name)
@section('admin_subtitle', 'عميل منذ '.dt($user->created_at))

@section('admin_actions')
    <a href="tel:{{ $user->phone }}" class="btn btn-outline btn-sm">{!! icon('phone', 'h-3.5 w-3.5') !!} اتصال</a>
    <a href="{{ route('admin.customers.index') }}" class="btn btn-outline btn-sm">{!! icon('arrow-right', 'h-3.5 w-3.5') !!} كل العملاء</a>
@endsection

@section('admin_content')
<div class="grid lg:grid-cols-3 gap-4">

    <div class="lg:col-span-2 space-y-4">
        {{-- طلبات العميل --}}
        <div class="card overflow-hidden">
            <div class="p-5 pb-3">
                <h2 class="font-bold flex items-center gap-2">{!! icon('clipboard-list', 'h-4 w-4 text-primary') !!} طلبات العميل ({{ $user->requests_count }})</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="table-noor min-w-[600px]">
                    <thead>
                        <tr>
                            <th>رقم الطلب</th>
                            <th>الجهاز</th>
                            <th>السعر</th>
                            <th>الفني</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $r)
                            <tr class="cursor-pointer" onclick="window.location='{{ route('admin.requests.show', $r) }}'">
                                <td><span class="text-primary font-bold" dir="ltr">{{ $r->order_number }}</span></td>
                                <td>{{ $r->device_type }}{{ $r->brand ? ' — '.$r->brand : '' }}</td>
                                <td class="font-bold">{{ $r->price ? money($r->price) : '—' }}</td>
                                <td class="text-xs">{{ $r->assignedTechnician->name ?? '—' }}</td>
                                <td>
                                    <span class="badge {{ $r->statusColor() }}">{{ $r->statusLabel() }}</span>
                                    @if ($r->review)
                                        <span class="badge bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-900 text-[9px]" title="تقييم {{ $r->review->rating }} نجوم">{{ $r->review->rating }}★</span>
                                    @endif
                                </td>
                                <td class="text-xs text-muted-foreground">{{ dt($r->created_at) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted-foreground py-6">مفيش طلبات</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $requests->links() }}</div>
        </div>

        {{-- ملاحظات العميل --}}
        <div class="card p-5" x-data="{ open: false }">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <h2 class="font-bold flex items-center gap-2">{!! icon('message', 'h-4 w-4 text-primary') !!} ملاحظات على العميل ({{ $notes->count() }})</h2>
                <button @click="open = !open" class="btn btn-outline btn-sm">{!! icon('plus', 'h-3.5 w-3.5') !!} ملاحظة</button>
            </div>

            <form x-show="open" x-transition method="POST" action="{{ route('admin.customers.notes', $user) }}" class="flex gap-2 mb-4" style="display:none">
                @csrf
                <input name="content" placeholder="ملاحظة عن العميل (تفضيلاته، تحذيرات...)" class="input flex-1" required>
                <button type="submit" class="btn btn-primary btn-sm">{!! icon('save', 'h-3.5 w-3.5') !!}</button>
            </form>

            <div class="space-y-2">
                @forelse ($notes as $note)
                    <div class="p-3 rounded-xl bg-muted/40 text-sm">
                        <div class="text-xs font-bold mb-1">{{ $note->author->name }} <span class="text-muted-foreground font-normal">— {{ dt($note->created_at, true) }}</span></div>
                        <p class="text-muted-foreground leading-relaxed">{{ $note->content }}</p>
                    </div>
                @empty
                    <p class="text-xs text-muted-foreground text-center py-3">مفيش ملاحظات — أضف ملاحظة عن العميل تفضل مع الفريق</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- العمود الجانبي --}}
    <div class="space-y-4">
        <div class="card p-5 text-center">
            <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-primary to-primary/70 text-primary-foreground text-2xl font-extrabold">
                {{ mb_substr($user->name, 0, 1) }}
            </div>
            <h3 class="font-bold">{{ $user->name }}</h3>
            <p class="text-muted-foreground text-sm" dir="ltr">{{ $user->phone }}</p>
            <span class="badge mt-2 {{ $user->is_active ? 'bg-green-100 dark:bg-green-950/40 text-green-700 dark:text-green-300 border-green-200 dark:border-green-900' : 'bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-300 border-red-200 dark:border-red-900' }}">{{ $user->is_active ? 'حساب نشط' : 'حساب معطل' }}</span>
        </div>

        <div class="card p-5 space-y-3">
            <h2 class="font-bold text-sm flex items-center gap-2">{!! icon('chart', 'h-4 w-4 text-primary') !!} إحصائيات العميل</h2>
            <div class="flex justify-between text-sm"><span class="text-muted-foreground">إجمالي الطلبات</span><b>{{ $user->requests_count }}</b></div>
            <div class="flex justify-between text-sm"><span class="text-muted-foreground">صيانات مكتملة</span><b>{{ $user->completed_count }}</b></div>
            <div class="flex justify-between text-sm"><span class="text-muted-foreground">إجمالي الإنفاق</span><b class="text-primary">{{ money($user->total_spent) }}</b></div>
            <div class="flex justify-between text-sm"><span class="text-muted-foreground">نقاط الولاء</span><b class="text-amber-600 dark:text-amber-400">{{ $user->loyalty_points }}</b></div>
        </div>

        @if ($totalDebt > 0)
            <div class="card p-5 border-amber-200 dark:border-amber-900 bg-amber-50/50 dark:bg-amber-950/20">
                <h2 class="font-bold text-sm mb-2 flex items-center gap-2">{!! icon('alert-circle', 'h-4 w-4 text-amber-600') !!} آجل مستحق</h2>
                <div class="text-2xl font-extrabold text-amber-600 dark:text-amber-400">{{ money($totalDebt) }}</div>
                <p class="text-[10px] text-muted-foreground mt-1">من طلبات مكتملة غير مسددة بالكامل</p>
            </div>
        @endif
    </div>
</div>
@endsection
