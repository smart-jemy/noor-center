@extends('admin.layout')
@section('title', 'العملاء — لوحة التحكم')
@section('admin_title', 'العملاء')
@section('admin_subtitle', 'قاعدة بيانات العملاء مع الولاء والديون')

@section('admin_content')
<form method="GET" class="card p-4 mb-4">
    <div class="flex flex-wrap gap-3 items-center">
        <div class="relative flex-1 min-w-[220px]">
            {!! icon('search', 'h-4 w-4 absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground') !!}
            <input name="q" value="{{ request('q') }}" placeholder="بحث بالاسم أو الهاتف..." class="input pr-10">
        </div>
        <label class="flex items-center gap-2 text-sm text-muted-foreground cursor-pointer select-none">
            <input type="checkbox" name="debtors" value="1" {{ request('debtors') ? 'checked' : '' }} class="accent-primary w-4 h-4">
            العملاء عليهم آجل فقط
        </label>
        <button type="submit" class="btn btn-primary btn-sm">{!! icon('filter', 'h-3.5 w-3.5') !!} فلترة</button>
        @if (request()->hasAny(['q', 'debtors']))
            <a href="{{ route('admin.customers.index') }}" class="btn btn-ghost btn-sm">مسح</a>
        @endif
    </div>
</form>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table-noor min-w-[720px]">
            <thead>
                <tr>
                    <th>العميل</th>
                    <th>الطلبات</th>
                    <th>مكتملة</th>
                    <th>إجمالي الإنفاق</th>
                    <th>نقاط الولاء</th>
                    <th>عضو منذ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $c)
                    <tr class="cursor-pointer" onclick="window.location='{{ route('admin.customers.show', $c) }}'">
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-primary to-primary/70 text-primary-foreground text-xs font-bold shrink-0">{{ mb_substr($c->name, 0, 1) }}</div>
                                <div>
                                    <div class="font-bold">{{ $c->name }}</div>
                                    <div class="text-[10px] text-muted-foreground" dir="ltr">{{ $c->phone }}</div>
                                </div>
                            </div>
                        </td>
                        <td><b>{{ $c->requests_count }}</b></td>
                        <td>{{ $c->completed_count }}</td>
                        <td class="font-bold text-primary">{{ money($c->total_spent) }}</td>
                        <td><span class="badge {{ $c->loyalty_points > 0 ? 'bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-900' : 'bg-muted text-muted-foreground border-border' }}">{!! icon('gift', 'h-3 w-3') !!} {{ $c->loyalty_points }}</span></td>
                        <td class="text-xs text-muted-foreground">{{ dt($c->created_at) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted-foreground py-8">مفيش عملاء مطابقين</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $customers->links() }}
@endsection
