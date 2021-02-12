document.addEventListener('DOMContentLoaded', function(){
    let s = document.createElement("script");
    s.type = "text/javascript";
    s.async = true;
    s.src = "https://api.mindbox.ru/scripts/v1/tracker.js";
    document.head.append(s);

    mindbox = window.mindbox || function() { mindbox.queue.push(arguments); };
    mindbox.queue = mindbox.queue || [];
    mindbox('create', {
        endpointId: '#endpointId#'
    });
});