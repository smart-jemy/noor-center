{{-- ============================================================
   جدول الطلبات الموحد — نفس الشكل عند الأدمن والاستقبال
   متغيرات:
   $requests   → paginator من الكنترولر
   $showRoute  → اسم مسار صفحة التفاصيل (reception.show أو admin.requests.show)
   $adminView  → true = عرض حذف الطلب (للأدمن بس)
   ============================================================ --}}
@php
    $showRoute = $showRoute ?? 'reception.show';
    $adminView = $adminView ?? false;
@endphp
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table-noor min-w-[980px]">
            <thead>
                <tr>
                    <th>رقم الطلب</th>
                    <th>العميل</th>
                    <th>الجهاز</th>
                    <th>الميعاد / المنطقة</th>
                    <th>الوضع / المهلة</th>
                    <th>الفني</th>
                    <th>السعر / الدفع</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $r)
                    <tr class="cursor-pointer {{ $r->isOverdue() ? 'bg-red-50/50 dark:bg-red-950/20' : '' }}"
                        onclick="if (event.target.closest('[data-no-nav]')) return; window.location='{{ route($showRoute, $r) }}'">
                        {{-- رقم الطلب --}}
                        <td>
                            <span class="text-primary font-extrabold font-mono text-[13px]" dir="ltr">{{ $r->order_number }}</span>
                            <div class="text-[10px] text-muted-foreground">{{ $r->timeAgo() }}</div>
                        </td>

                        {{-- العميل --}}
                        <td>
                            <div class="font-bold">{{ $r->customer->name }}</div>
                            <div class="text-[10px] text-muted-foreground" dir="ltr">{{ $r->customer->phone }}</div>
                        </td>

                        {{-- الجهاز --}}
                        <td>
                            <div class="flex items-center gap-1.5">
                                {!! device_icon($r->device_type, 'h-4 w-4 text-muted-foreground') !!}
                                <span>{{ $r->device_type }}{{ $r->brand ? ' — '.$r->brand : '' }}</span>
                            </div>
                            @if ($r->department)<div class="text-[10px] text-muted-foreground">{{ $r->department->name }}</div>@endif
                        </td>

                        {{-- الميعاد / المنطقة --}}
                        <td class="text-xs">
                            @if ($r->preferred_date)
                                <div>{{ dt($r->preferred_date) }}@if ($r->preferred_time) — {{ $r->timeSlotLabel() }}@endif</div>
                            @else
                                <div class="text-muted-foreground">بدون ميعاد محدد</div>
                            @endif
                            <div class="text-[10px] text-muted-foreground">{{ $r->areaLabel() }}</div>
                            @if (! in_array($r->status, ['COMPLETED', 'CANCELLED']))
                                <div class="text-[10px] font-bold {{ $r->isOverdue() ? 'text-red-600 dark:text-red-400' : 'text-muted-foreground' }}">
                                    {{ $r->isOverdue() ? $r->slaRemainingLabel().' — متأخر!' : $r->slaRemainingLabel() }}
                                </div>
                            @endif
                        </td>

                        {{-- الوضع (استعجال + مرتجع) --}}
                        <td class="text-xs">
                            <span class="urgency-flag {{ $r->urgencyColor() }}">{{ $r->urgencyLabel() }}</span>
                            @if ($r->isImmediate())
                                <span class="inline-flex items-center gap-0.5 text-[9px] font-bold text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-950/50 px-1.5 py-0.5 rounded-md mt-0.5">{!! icon('zap', 'h-2.5 w-2.5') !!} فوري</span>
                            @endif
                            @if ($r->return_count > 0)
                                <span class="badge bg-orange-100 text-orange-800 border-orange-300 dark:bg-orange-950/50 dark:text-orange-300 dark:border-orange-800 text-[9px] mt-0.5">مرتجع {{ $r->return_count }}×</span>
                            @endif
                        </td>

                        {{-- الفني --}}
                        <td class="text-xs">{{ $r->assignedTechnician->name ?? '—' }}</td>

                        {{-- السعر / الدفع --}}
                        <td class="font-bold {{ $r->price ? 'text-primary' : 'text-muted-foreground' }}">
                            {{ $r->price ? money($r->price) : '—' }}
                            @if ($r->payment_method)
                                <div class="text-[10px] font-extrabold {{ $r->payment_method === 'cash' ? 'text-green-600 dark:text-green-400' : 'text-blue-600 dark:text-blue-400' }}">{{ $r->paymentLabel() }}</div>
                            @endif
                        </td>

                        {{-- الحالة --}}
                        <td><span class="badge {{ $r->statusColor() }}"><span class="h-1.5 w-1.5 rounded-full {{ $r->statusDot() }}"></span>{{ $r->statusLabel() }}</span></td>

                        {{-- الإجراءات: زر سريع للمرحلة التالية في دورة حياة الطلب (كشف ← تواصل ← تأكيد ← تنفيذ ← جاهز ← تسليم) --}}
                        <td data-no-nav>
                            <div class="flex items-center gap-1">
                                @php
                                    $next = $r->nextStatus();
                                    $nextStyle = [
                                        'CONTACTED' => 'bg-cyan-100 dark:bg-cyan-950/60 text-cyan-700 dark:text-cyan-300',
                                        'CONFIRMED' => 'bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300',
                                        'IN_PROGRESS' => 'bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300',
                                        'READY' => 'bg-teal-100 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300',
                                    ][$next ?? ''] ?? null;
                                    $nextLabel = [
                                        'CONTACTED' => 'تواصل',
                                        'CONFIRMED' => 'تأكيد',
                                        'IN_PROGRESS' => $r->status === 'RETURNED' ? 'بدء إعادة الإصلاح' : 'بدء التنفيذ',
                                        'READY' => 'جاهز',
                                    ][$next ?? ''] ?? null;
                                @endphp
                                @if ($next && $next !== 'COMPLETED' && $nextStyle)
                                    <form method="POST" action="{{ route('reception.update', $r) }}" class="inline">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="status" value="{{ $next }}">
                                        <input type="hidden" name="price" value="{{ $r->price }}">
                                        <input type="hidden" name="admin_notes" value="{{ $r->admin_notes }}">
                                        @if ($r->status === 'RETURNED')
                                            <button type="submit" class="px-2 py-1 rounded-md text-[10px] font-bold text-white hover:opacity-90" style="background: oklch(0.7 0.17 55)">{{ $nextLabel }}</button>
                                        @else
                                            <button type="submit" class="px-2 py-1 rounded-md text-[10px] font-bold hover:opacity-80 {{ $nextStyle }}">{{ $nextLabel }}</button>
                                        @endif
                                    </form>
                                @elseif ($next === 'COMPLETED')
                                    {{-- التسليم = تحصيل كامل السعر — اختيار طريقة الدفع (كاش/تحويل) --}}
                                    <form method="POST" action="{{ route('reception.update', $r) }}" class="inline-flex items-center gap-1">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="status" value="COMPLETED">
                                        <input type="hidden" name="price" value="{{ $r->price }}">
                                        <input type="hidden" name="admin_notes" value="{{ $r->admin_notes }}">
                                        <input type="hidden" name="warranty_months" value="0">
                                        <select name="payment_method" class="px-1 py-1 rounded-md text-[10px] font-bold border border-green-200 dark:border-green-900 bg-green-50 dark:bg-green-950/60 text-green-700 dark:text-green-300">
                                            <option value="cash">كاش</option>
                                            <option value="transfer">تحويل</option>
                                        </select>
                                        <button type="submit" class="px-2 py-1 rounded-md text-[10px] font-bold bg-green-100 dark:bg-green-950/60 text-green-700 dark:text-green-300 hover:opacity-80">تسليم!</button>
                                    </form>
                                @endif
                                <a href="tel:{{ $r->phone }}" title="اتصال بالعميل" class="flex h-6 w-6 items-center justify-center rounded-md bg-muted hover:bg-muted/70 text-muted-foreground">{!! icon('phone', 'h-3 w-3') !!}</a>
                                <a href="{{ route('reception.receipt', $r) }}" target="_blank" title="طباعة إيصال" class="flex h-6 w-6 items-center justify-center rounded-md bg-muted hover:bg-muted/70 text-muted-foreground">{!! icon('printer', 'h-3 w-3') !!}</a>
                                @if ($adminView)
                                    <form method="POST" action="{{ route('admin.requests.destroy', $r) }}" class="inline" onsubmit="return confirm('سيتم حذف كل البيانات المرتبطة به نهائياً — متأكد؟')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="flex h-6 w-6 items-center justify-center rounded-md bg-red-100 dark:bg-red-950/60 text-red-600 dark:text-red-400 hover:opacity-80" title="حذف الطلب">{!! icon('trash', 'h-3 w-3') !!}</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted-foreground py-10">مفيش طلبات مطابقة للبحث — جرب تغير الفلتر أو تكتب كلمة تانية</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
