<?php

/*
 * Ziggy serialises the named route table into every HTML response, including
 * the pre-auth login page, so anything the Vue app never calls route() for is
 * pure payload. These are vendor dashboards and internals reached by typing a
 * URL, not by a route() helper — excluding them keeps roughly a quarter of the
 * route table out of every page. Application routes stay in: the admin panel
 * builds its links with route(), so it cannot be filtered out here.
 */
return [
    'except' => [
        'horizon.*',
        'telescope.*',
        'log-viewer.*',
        'sanctum.*',
    ],
];
