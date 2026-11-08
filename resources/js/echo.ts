import Echo from "laravel-echo";
import Pusher from "pusher-js";

declare global {
  interface Window {
    Pusher: typeof Pusher;
    Echo: Echo<any>;
  }
}

window.Pusher = Pusher;

export const createEchoClient = () => {
  const isHttps =
    typeof window !== "undefined" && window.location.protocol === "https:";
  const defaultHost =
    typeof window !== "undefined" &&
    window.location.hostname.includes("siamialghivari")
      ? window.location.hostname.replace(
          "siamialghivari",
          "siamialghivarireverb",
        )
      : typeof window !== "undefined"
        ? window.location.hostname
        : "localhost";

  const wsHost = import.meta.env.VITE_REVERB_HOST || defaultHost;
  const isProductionDomain = wsHost.includes("dpdns.org") || isHttps;

  return new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY || "sia-alghivari-key",
    wsHost,
    wsPort: isProductionDomain
      ? 443
      : Number(import.meta.env.VITE_REVERB_PORT) || 8080,
    wssPort: isProductionDomain
      ? 443
      : Number(import.meta.env.VITE_REVERB_PORT) || 8080,
    forceTLS:
      isProductionDomain || import.meta.env.VITE_REVERB_SCHEME === "https",
    enabledTransports: ["ws", "wss"],
  });
};
