const CACHE_NAME = "ligaepica-cache-v2";

const urlsToCache = [
  "/ligaepica_v2/",
  "/ligaepica_v2/index.php",
  "/ligaepica_v2/assets/img/app-icon.png"
];

/* INSTALACIÓN */

self.addEventListener("install", event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
});

/* ACTIVACIÓN */

self.addEventListener("activate", event => {
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys.filter(key => key !== CACHE_NAME)
            .map(key => caches.delete(key))
      );
    })
  );
});

/* FETCH (CARGA RÁPIDA) */

self.addEventListener("fetch", event => {

  event.respondWith(

    caches.match(event.request).then(response => {

      if(response){
        return response;
      }

      return fetch(event.request).then(networkResponse => {

        return caches.open(CACHE_NAME).then(cache => {

          cache.put(event.request, networkResponse.clone());

          return networkResponse;

        });

      });

    })

  );

});