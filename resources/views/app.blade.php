<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>JOBS SMS</title>
    <meta name="description" content="Plateforme de gestion de campagnes SMS">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="alternate icon" href="/favicon.ico" />

    @vite(['resources/src/main.ts'])
  </head>

  <body>
    <div id="root"></div>
  </body>
</html>
