if ("serviceWorker" in navigator) {
  window.addEventListener("load", async () => {
    try {
      const registrations = await navigator.serviceWorker.getRegistrations();
      await Promise.all(
        registrations.map((registration) => registration.unregister()),
      );
      console.log(
        "Service Worker désactivé et tous les enregistrements supprimés.",
      );
    } catch (error) {
      console.warn("Impossible de désenregistrer le Service Worker:", error);
    }

    if (window.caches && typeof window.caches.keys === "function") {
      try {
        const cacheNames = await caches.keys();
        await Promise.all(cacheNames.map((name) => caches.delete(name)));
        console.log("Caches PWA supprimés:", cacheNames);
      } catch (error) {
        console.warn("Impossible de supprimer les caches PWA:", error);
      }
    }
  });
}
