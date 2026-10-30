/*
 * Puts Leaflet on the global object at import time.
 *
 * This exists as its own module purely for ordering. leaflet.heat is a UMD
 * plugin written before modules existed: it references a bare `L` at evaluation
 * time instead of importing one, so it throws "L is not defined" unless `L` is
 * already global when it runs.
 *
 * The obvious fix — a dynamic import after assigning the global — splits the
 * plugin into a second lazy chunk, which puts another network request on the
 * critical path of a public page, and leaves the map building before the heat
 * layer has loaded. A 5 kB plugin is not worth a second round trip, and a heat
 * layer that is sometimes missing is worse than one that is always present.
 *
 * So instead this module sets the global as a side effect of a static import,
 * and the plugin is imported after it. ES modules evaluate their imports in
 * declaration order, so by the time leaflet.heat runs, `L` is already there.
 */
import L from "leaflet";

globalThis.L = L;

export default L;
