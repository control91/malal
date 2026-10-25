<?php

// Only public route names are exposed to anonymous visitors; the back-office gets them all.
return [
    'groups' => [
        'public' => ['home', 'expertise', 'solutions.*', 'pricing', 'blog.*', 'cases.*', 'faq', 'careers*', 'partners', 'status', 'contact*', 'quote*', 'demo*', 'thanks', 'newsletter.*', 'page', 'login*', 'password.*', 'logout'],
    ],
];
