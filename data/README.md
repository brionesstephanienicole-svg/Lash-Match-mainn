# Cavite service area

`cavite-boundary.geojson` contains the Cavite feature from the geoBoundaries Philippines ADM2 simplified dataset, revision `41af8f1`.

- Source: https://www.geoboundaries.org/api/current/gbOpen/PHL/ADM2/
- Geometry: https://media.githubusercontent.com/media/wmgeolab/geoBoundaries/41af8f1/releaseData/gbOpen/PHL/ADM2/geoBoundaries-PHL-ADM2_simplified.geojson
- Original sources: National Mapping and Resource Information Authority (NAMRIA), Philippines Statistics Authority (PSA), OCHA Philippines.
- License: Creative Commons Attribution 3.0 IGO, https://creativecommons.org/licenses/by/3.0/igo/
- Modification: extracted Cavite and added source/attribution metadata. Geometry is unchanged from the simplified dataset.

The browser and PHP backend use the same polygon, including its islands and holes. This is a service-area check using a simplified administrative boundary; locations immediately on a border may need boundary data review.

Address lookup uses Photon (OpenStreetMap data): https://github.com/komoot/photon. The public demo is intended for reasonable request volumes and has no availability guarantee. Requests are authenticated, debounced in the UI, throttled and cached in the session. The address and selected city are sent to Photon; no account details are sent. If it is unavailable or has no address match, select the location manually on the Cavite map.
