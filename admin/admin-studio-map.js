/* Leaflet location picker: the marker and form always use the same coordinates. */
(() => {
'use strict';
const $=s=>document.querySelector(s), center=[14.28,120.87];
const icon=()=>L.divIcon({className:'studio-location-marker',html:'<span></span>',iconSize:[24,32],iconAnchor:[12,32]});
function point(lat,lng){
 if(lat===null||lng===null||String(lat).trim()===''||String(lng).trim()==='')return null;
 lat=Number(lat);lng=Number(lng);
 return Number.isFinite(lat)&&Number.isFinite(lng)&&window.CaviteArea.contains(lat,lng)?[lat,lng]:null;
}
function tiles(map,onError){return L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap contributors</a>'}).addTo(map).on('tileerror',onError);}
window.initStudioMap=()=>{
 const host=$('#studio-location-preview'),status=$('#studio-location-status');
 let preview=null,marker=null;
 if(window.L){
  preview=L.map(host,{maxBounds:window.CaviteArea.bounds,maxBoundsViscosity:1,minZoom:9,scrollWheelZoom:false,dragging:false,touchZoom:false,doubleClickZoom:false,boxZoom:false,keyboard:false,zoomControl:false}).setView(center,10);
  tiles(preview,()=>{status.textContent='Map tiles could not load. Coordinates are still available below.';});
  new ResizeObserver(()=>preview.invalidateSize()).observe(host);
 }
 window.updateStudioMap=()=>{
  const selected=point($('#studio-latitude').value,$('#studio-longitude').value);
  status.textContent=selected?'Selected pin: '+selected.map(n=>n.toFixed(6)).join(', '):'Enter an address to find its pin, or pick a location inside Cavite.';
  if(!preview){status.textContent+=' The map library could not load.';return;}
  if(marker){preview.removeLayer(marker);marker=null;}
  preview.invalidateSize();
  preview.setView(selected||center,selected?16:10,{animate:false});
  if(selected)marker=L.marker(selected,{icon:icon(),interactive:false}).addTo(preview);
 };

 let generation=0,timer=null,pending=false;
 const address=$('#studio-address'),city=$('#studio-city'),lat=$('#studio-latitude'),lng=$('#studio-longitude');
 const results=document.createElement('select');results.id='studio-address-results';results.hidden=true;results.setAttribute('aria-label','Matching addresses in Cavite');results.style.cssText='width:100%;margin:8px 0;';status.after(results);
 const retry=document.createElement('button');retry.type='button';retry.className='manager-button';retry.textContent='Find address';results.after(retry);
 let matches=[];
 window.cancelStudioLookup=()=>{generation++;clearTimeout(timer);pending=false;results.hidden=true;matches=[];};
 window.validStudioLocation=()=>{
  if(pending){$('#form-message').textContent='Wait for the address search, or select a pin on the map.';return false;}
  if(!point(lat.value,lng.value)){$('#form-message').textContent='Select a studio pin inside Cavite.';return false;}
  return true;
 };
 function applyMatch(match){lat.value=Number(match.latitude).toFixed(6);lng.value=Number(match.longitude).toFixed(6);city.value=match.city;window.updateStudioMap();status.textContent='Address match: '+match.label+'. Check the pin and adjust it to the studio entrance if needed.';}
 async function lookup(version){
  const text=address.value.trim(),selectedCity=city.value;
  if(text.length<5){pending=false;status.textContent='Enter a complete street or landmark address in Cavite.';return;}
  pending=true;status.textContent='Finding the address in Cavite...';
  try{
   await window.CaviteArea.ready;if(version!==generation)return;
   const response=await window.studioAddressSearch({address:text,city:selectedCity});
   if(version!==generation)return;
   matches=(response.locations||[]).filter(m=>point(m.latitude,m.longitude));
   if(!matches.length){status.textContent='Address not found in Cavite. Add the barangay or landmark, or pick the location on the map.';return;}
   results.replaceChildren(...matches.map((m,i)=>new Option(m.label,String(i))));results.hidden=matches.length<2;
   applyMatch(matches[0]);
  }catch(e){if(version===generation)status.textContent=e.message||'Address search is unavailable. Pick the location on the map.';}
  finally{if(version===generation)pending=false;}
 }
 function schedule(){window.cancelStudioLookup();lat.value='';lng.value='';window.updateStudioMap();pending=true;status.textContent='Waiting for the address...';const version=generation;timer=setTimeout(()=>lookup(version),1400);}
 address.addEventListener('input',schedule);city.addEventListener('change',schedule);
 retry.onclick=()=>{window.cancelStudioLookup();lat.value='';lng.value='';window.updateStudioMap();lookup(generation);};
 results.onchange=()=>applyMatch(matches[Number(results.value)]);
 ['#studio-latitude','#studio-longitude'].forEach(id=>$(id).addEventListener('input',()=>{window.cancelStudioLookup();window.updateStudioMap();if(!point(lat.value,lng.value))status.textContent='Enter both coordinates inside Cavite.';}));
 window.CaviteArea.ready.then(feature=>{if(preview)L.geoJSON(feature,{attribution:'Cavite boundary: <a href="https://www.geoboundaries.org/">geoBoundaries</a> / NAMRIA, PSA, OCHA (<a href="https://creativecommons.org/licenses/by/3.0/igo/">CC BY 3.0 IGO</a>)',interactive:false,style:{color:'#ed3681',weight:2,fillOpacity:.04}}).addTo(preview);window.updateStudioMap();}).catch(e=>{status.textContent=e.message;});
 $('#pick-location').onclick=()=>{window.cancelStudioLookup();window.openStudioMap({latitude:$('#studio-latitude').value,longitude:$('#studio-longitude').value,onSelect:(lat,lng)=>{
  $('#studio-latitude').value=lat.toFixed(6);$('#studio-longitude').value=lng.toFixed(6);window.updateStudioMap();
 }});};
 window.updateStudioMap();
};
window.openStudioMap=({latitude,longitude,onSelect})=>{
 const dialog=$('#admin-dialog');let selected=point(latitude,longitude),map=null,marker=null,accuracy=null,disposed=false;
 $('#dialog-title').textContent='Pick Studio Location';
 $('#dialog-content').innerHTML='<p>Cavite studios only. Click the studio building or drag the pin to its entrance. Check that it matches the address and city in your form.</p><div id="location-map" class="location-picker" aria-label="Studio location map"></div><p id="map-status" class="map-status" role="status"></p><form id="location-form"><div class="map-coordinate-fields"><label>Latitude<input id="map-latitude" type="number" min="-85" max="85" step="any" required></label><label>Longitude<input id="map-longitude" type="number" min="-180" max="180" step="any" required></label></div><div class="map-footer"><button type="button" class="manager-button" id="map-current">Use My Location</button><button type="button" class="manager-button" id="map-cancel">Cancel</button><button type="submit" class="manager-button manager-primary">Use This Location</button></div></form>';
 const lat=$('#map-latitude'),lng=$('#map-longitude'),status=$('#map-status'),current=$('#map-current');
 if(selected){lat.value=selected[0].toFixed(6);lng.value=selected[1].toFixed(6);}
 status.textContent=selected?'Saved coordinates loaded. Move the pin only if needed.':'No pin selected. Click the exact studio location or enter its coordinates.';
 if(!dialog.open)dialog.showModal();
 function place(value,recenter=false){
  selected=value;
  if(accuracy&&map){map.removeLayer(accuracy);accuracy=null;}
  if(!map)return;
  if(!value){if(marker){map.removeLayer(marker);marker=null;}return;}
  if(marker)marker.setLatLng(value);
  else{
   marker=L.marker(value,{icon:icon(),draggable:true,autoPan:true,title:'Studio location: drag to adjust'}).addTo(map);
   marker.on('dragend',()=>{const p=marker.getLatLng().wrap();lat.value=p.lat.toFixed(6);lng.value=p.lng.toFixed(6);place(point(lat.value,lng.value));status.textContent=selected?'Pin updated. Confirm the studio entrance.':'Outside Cavite. Select a pin inside the highlighted boundary.';});
  }
  if(recenter)map.setView(value,Math.max(map.getZoom(),16),{animate:false});
 }
 if(window.L){
  map=L.map('location-map',{maxBounds:window.CaviteArea.bounds,maxBoundsViscosity:1,minZoom:9}).setView(selected||center,selected?17:10);
  tiles(map,()=>{status.textContent='Map tiles could not load. Enter known coordinates or try again when online.';});
  window.CaviteArea.ready.then(feature=>{if(!disposed)L.geoJSON(feature,{attribution:'Cavite boundary: <a href="https://www.geoboundaries.org/">geoBoundaries</a> / NAMRIA, PSA, OCHA (<a href="https://creativecommons.org/licenses/by/3.0/igo/">CC BY 3.0 IGO</a>)',interactive:false,style:{color:'#ed3681',weight:2,fillOpacity:.04}}).addTo(map);}).catch(()=>{status.textContent='Cavite boundary could not load. Refresh the page.';});
  place(selected);
  map.on('click',e=>{const p=e.latlng.wrap();lat.value=p.lat.toFixed(6);lng.value=p.lng.toFixed(6);place(point(lat.value,lng.value));status.textContent=selected?'Pin updated. Confirm the studio entrance.':'Outside Cavite. Select a pin inside the highlighted boundary.';});
  requestAnimationFrame(()=>{if(!disposed)map.invalidateSize();});
 }else status.textContent='Map could not load. Enter known coordinates below.';
 const resize=new ResizeObserver(()=>{if(map&&!disposed)map.invalidateSize();});resize.observe($('#location-map'));
 [lat,lng].forEach(input=>input.oninput=()=>{place(point(lat.value,lng.value),true);status.textContent=selected?'Pin matches the entered coordinates.':'Enter coordinates inside Cavite.';});
 $('#map-cancel').onclick=()=>dialog.close();
 current.onclick=()=>{
  if(!navigator.geolocation){status.textContent='Location is unavailable. Click the map or enter coordinates.';return;}
  current.disabled=true;status.textContent='Finding your location...';
  navigator.geolocation.getCurrentPosition(p=>{
   if(disposed)return;current.disabled=false;
   const value=point(p.coords.latitude,p.coords.longitude);
   if(!value){status.textContent='Your location is outside Cavite. Pick the studio location inside Cavite.';return;}
   lat.value=value[0].toFixed(6);lng.value=value[1].toFixed(6);place(point(lat.value,lng.value),true);
   if(map)accuracy=L.circle(selected,{radius:p.coords.accuracy,color:'#ed3681',weight:1,fillOpacity:.12}).addTo(map);
   status.textContent='Device location accuracy: about '+Math.ceil(p.coords.accuracy)+' meters. Use this only if you are at the studio; adjust the pin if needed.';
  },()=>{if(disposed)return;current.disabled=false;status.textContent='Unable to get your location. Allow location access, click the map, or enter coordinates.';},{enableHighAccuracy:true,timeout:15000,maximumAge:0});
 };
 $('#location-form').onsubmit=e=>{
  e.preventDefault();const value=point(lat.value,lng.value);
  if(!value){status.textContent='Select a location inside Cavite.';return;}
  onSelect(...value);dialog.close();
 };
 dialog.addEventListener('close',()=>{disposed=true;resize.disconnect();if(map)map.remove();},{once:true});
};
})();