(function () {
    var iframe = document.createElement('iframe');
    iframe.src = import.meta.env.VITE_APP_URL + '/widget';
    iframe.style.width = '3.5rem';
    iframe.style.height = '3.5rem';
    iframe.style.border = '3.5rem';
    iframe.scrolling = 'no';
    iframe.id = 'chatbot_iframe';

    var div = document.createElement('div');
    div.style.position = 'fixed';
    div.style.bottom = '10px';
    div.style.right = '10px';
    div.appendChild(iframe);
    document.body.appendChild(div);

    window.addEventListener('message', function (event) {
        const el = document.getElementById('chatbot_iframe');
        if (event.data.action === 'chatbot_toggle') {
            if (event.data.open) {
                el.style.height = '420px';
                el.style.width = '300px';
            } else {
                setTimeout(function () {
                    el.style.height = '3.5rem';
                    el.style.width = '3.5rem';
                }, 300)
            }
        }
    });
})()
