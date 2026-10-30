/*
 * Leaflet, bundled rather than fetched from a CDN.
 *
 * It used to be pulled from unpkg on every page that draws a map. That is a
 * third-party network request on the critical path of a complaint being filed,
 * and it is the least reliable link in the chain on the device that matters
 * most: a phone on mobile data, behind a captive portal, or with an ad blocker
 * that reads url paths as trackers. When it failed, the map silently did not
 * appear and the resident was left staring at an empty box with no idea whether
 * their location had been captured.
 *
 * Bundling it also removes a render-blocking request from a form that people
 * open on a slow connection, which is the same complaint from the other side.
 *
 * The queue this flushes is defined by resources/views/partials/leaflet-bootstrap
 * as a classic script in the document head, because a bundled module is deferred
 * and cannot be reached by the page scripts that draw the maps. See that file
 * for why the ordering is the way it is.
 */

// Must come first: this sets the global that leaflet.heat expects to find.
import L from "./leaflet-global";
import "leaflet.heat";
import "leaflet/dist/leaflet.css";

/*
 * The default marker.
 *
 * Leaflet finds its default icon by sniffing the stylesheet for the image paths
 * at runtime. That works when the CSS is a plain <link> and breaks when a
 * bundler has rewritten the URLs, leaving a marker that renders as a broken
 * image. The paths are resolved here, where the bundler can see them, and the
 * sniffer is removed so it cannot overwrite them.
 */
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

window.L = L;
window.leafletReady = true;

window.__flushLeafletWaiters();

export default L;
