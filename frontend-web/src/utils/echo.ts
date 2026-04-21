/**
 * Safe no-op Echo for prod. Prevents realtime from breaking the UI.
 * Provides the fields many apps read (connector/options/socketId/pusher).
 */
type Chain = any;
const chain: any = new Proxy(function(){}, { get: () => chain, apply: () => chain });

const fakeConnector: any = {
  options: {},                        // some code reads Echo.connector.options
  socketId: () => null,               // some code calls Echo.connector.socketId()
  pusher: { connection: { state: 'disconnected' } }, // common health checks
};

const echo: any = {
  connector: fakeConnector,
  channel: () => chain,
  private: () => chain,
  join: () => chain,
  leave: () => {},
  disconnect: () => {},
};

// expose globally in case something references window.Echo
try { (window as any).Echo = echo; } catch {}

export default echo;
