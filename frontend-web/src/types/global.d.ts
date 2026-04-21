import type Echo from 'laravel-echo';

declare global {
  interface Window {
    Pusher: any;              // keep as 'any' to match your existing file
    echo: Echo<'pusher'>;
  }
}

export {};
