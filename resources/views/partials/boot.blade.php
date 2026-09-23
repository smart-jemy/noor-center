{{-- ============================================================
   شاشة الإقلاع (Boot Screen) ثلاثية الأبعاد — لوجو NC
   تظهر بعد تسجيل دخول الأدمن والاستقبال لمدة 3 ثواني بالظبط
   ثم تتلاشى وتتشال من الـ DOM — مرة واحدة (session flash)
   ============================================================ --}}
@if (session('boot_screen'))
    @php $bootTarget = session('boot_screen'); @endphp
    <div id="nc-boot" class="nc-boot" role="status" aria-label="جاري تحميل النظام">
        <div class="nc-boot-glow"></div>

        <div class="nc-boot-stage">
            {{-- الشعار ثلاثي الأبعاد — مكعب NC يدور ببطء مع انعكاس ضوئي --}}
            <div class="nc-boot-scene">
                <div class="nc-boot-cube">
                    <div class="nc-boot-face nc-boot-front">
                        <span class="nc-boot-mono">NC</span>
                    </div>
                    <div class="nc-boot-face nc-boot-back">
                        <span class="nc-boot-mono">NC</span>
                    </div>
                    <div class="nc-boot-face nc-boot-side"></div>
                </div>
                <div class="nc-boot-shadow"></div>
            </div>

            <h1 class="nc-boot-title">مركز نور لخدمات الصيانة</h1>
            <p class="nc-boot-sub">جاري تحضير <b>{{ $bootTarget }}</b> — لحظة واحدة</p>

            {{-- شريط تقدم 3 ثواني --}}
            <div class="nc-boot-progress"><div class="nc-boot-bar"></div></div>

            <div class="nc-boot-dots">
                <span></span><span></span><span></span>
            </div>
        </div>

        <div class="nc-boot-foot">Noor Center &copy; {{ date('Y') }} — نظام إدارة الصيانة</div>
    </div>

    <script>
        (function () {
            var el = document.getElementById('nc-boot');
            if (!el) return;
            // 3 ثواني بالظبط ثم تتلاشى وتتشال
            setTimeout(function () {
                el.classList.add('nc-boot-out');
                setTimeout(function () {
                    el.remove();
                    document.body.style.overflow = '';
                }, 450);
            }, 3000);
            document.body.style.overflow = 'hidden';
        })();
    </script>
@endif
