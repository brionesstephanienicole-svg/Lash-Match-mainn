/* Boundary shared with studio-scope.php. See data/cavite-boundary.geojson attribution. */
(() => {
'use strict';
let geometry;
function ringContains(x,y,ring){let inside=false;for(let i=0,j=ring.length-1;i<ring.length;j=i++){
 const [a,b]=ring[i],[c,d]=ring[j];const cross=(x-a)*(d-b)-(y-b)*(c-a);
 if(Math.abs(cross)<1e-12&&x>=Math.min(a,c)&&x<=Math.max(a,c)&&y>=Math.min(b,d)&&y<=Math.max(b,d))return true;
 if((b>y)!==(d>y)&&x<(c-a)*(y-b)/(d-b)+a)inside=!inside;
}return inside;}
window.CaviteArea={bounds:[[14.05,120.55],[14.53,121.08]],contains(lat,lng){
 if(!geometry||!Number.isFinite(lat)||!Number.isFinite(lng))return false;
 const polygons=geometry.type==='Polygon'?[geometry.coordinates]:geometry.coordinates;
 return polygons.some(rings=>ringContains(lng,lat,rings[0])&&!rings.slice(1).some(ring=>ringContains(lng,lat,ring)));
},ready:fetch('../data/cavite-boundary.geojson').then(r=>{if(!r.ok)throw Error('Cavite boundary could not load. Refresh the page.');return r.json();}).then(feature=>{geometry=feature.geometry;return feature;})};
// Keep rejection handled until the map is initialized.
window.CaviteArea.ready.catch(()=>{});
})();
