<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
        <script>(function () { var e=document.createElement("iframe");e.src="http://localhost:8000/widget/widget";e.style.width="3.5rem";e.style.height="3.5rem";e.style.border="3.5rem";e.scrolling="no";e.id="chatbot_iframe";var t=document.createElement("div");t.style.position="fixed";t.style.bottom="10px";t.style.right="10px";t.appendChild(e);document.body.appendChild(t);window.addEventListener("message",function(d){const i=document.getElementById("chatbot_iframe");d.data.action==="chatbot_toggle"&&(d.data.open?(i.style.height="420px",i.style.width="300px"):setTimeout(function(){i.style.height="3.5rem",i.style.width="3.5rem"},300))});})();
        </script>
        <div style="position: fixed; bottom: 10px; right: 10px;">
            <iframe src="{{ env('VITE_APP_URL', null) }}" style="width: 3.5rem; height: 3.5rem; border: none; overflow: hidden;" scrolling="no"></iframe>
        </div>
    </body>
</html>
