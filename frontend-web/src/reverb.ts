/**
 * Reverb/Echo stub for environments where realtime is not configured.
 * Keep this file so "import './reverb'" always resolves.
 * Replace with a real setup when you’re ready.
 */
export function setupRealtime() {
  if (import.meta.env.DEV) {
    console.info('[realtime] disabled (stub)');
  }
}
// Auto-run to match "import './reverb'"
setupRealtime();
