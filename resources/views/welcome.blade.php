<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>АйТи Спец</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        @vite('resources/css/app.css')
        @vite('resources/js/app.js')

        @production
        <!-- Yandex.Metrika counter -->
        <script type="text/javascript">
            (function(m,e,t,r,i,k,a){
                m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
                m[i].l=1*new Date();
                for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
                k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
            })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=104710911', 'ym');

            ym(104710911, 'init', {ssr:true, clickmap:true, ecommerce:"dataLayer", accurateTrackBounce:true, trackLinks:true});
        </script>
        <noscript><div><img src="https://mc.yandex.ru/watch/104710911" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
        <!-- /Yandex.Metrika counter -->
        @endproduction


    </head>
    <body class="bg-[#000101] text-white min-h-screen flex items-center justify-center">
        <div class="flex flex-col items-center gap-8 text-center px-4">
            <img src="{{ asset('img/ayti.png') }}" alt="АйТи Спец" class="w-full max-w-[640px] md:max-w-[720px] rounded-[64px]">
            <div id="joke" class="text-lg leading-relaxed max-w-xl text-white/70 transition-opacity duration-500 ease-out opacity-0 min-h-[4.5rem] flex items-center justify-center px-4">
                Загрузка...
            </div>
            <p class="text-xs text-white/30">
                АйТи Спец. v{{ aytispec_version() }}
            </p>
        </div>

        <script>
            const jokes = [
              "Программист сделал коммит перед сном — чтобы сон был версионирован.",
              "Вам сказали думать вне коробки — программист запустил Docker.",
              "Если баг нельзя воспроизвести — значит он сам себя исправил.",
              "«У меня работает» — официальное название локальной реальности.",
              "Кофе для программиста — это топливо для цикла while(true).",
              "Документация — это карта сокровищ, которую никто не читает до конца.",
              "Баг в продакшне — это фича, у которой закончились оправдания.",
              "Код без комментариев — это шпионский роман с секретными главами.",
              "Оптимизировать сначала — значит вызвать распродажу багов.",
              "Перезагрузка — магия, которую не верят до тех пор, пока она не работает.",
              "Программист в отпуске — тот же баг, но в другом окружении.",
              "Программист не боится темноты — он боится merge‑конфликтов.",
              "Когда программист говорит «потом», он имеет в виду «в следующем билде».",
              "Лучший друг программиста — IDE; худший — дедлайн.",
              "Кофе в руках программиста — это указатель на активную сессию.",
              "Комментарии в коде — это письма от прошлого себя к будущему.",
              "Программист увидел свет в конце туннеля — это был лог.",
              "Где двух программистов — там три мнения о стиле кода.",
              "Программист идёт на свидание с баг-репортером — ожидает воспроизведения.",
              "Любовь программиста — стабильная релиз-ветка и пустой таск-лист."
            ];

            const jokeEl = document.getElementById('joke');

            function showRandom() {
              const idx = Math.floor(Math.random() * jokes.length);
              jokeEl.textContent = jokes[idx];
            }

            function fadeToRandom(initial = false) {
              if (!initial) {
                jokeEl.classList.add('opacity-0');
                setTimeout(() => {
                  showRandom();
                  jokeEl.classList.remove('opacity-0');
                }, 250);
              } else {
                showRandom();
                requestAnimationFrame(() => jokeEl.classList.remove('opacity-0'));
              }
            }

            fadeToRandom(true);
            setInterval(() => fadeToRandom(), 23000);
        </script>
    </body>
</html>
