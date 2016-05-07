(()=>{"use strict";// This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0. If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
const data = JSON.parse(document.currentScript.dataset.json);
const convert = (x, isLink) => (
	Array.isArray(x) ? {'':'ol', c:x.map(function(y){return{'':'li', c:convert(y, isLink)}})} :
	typeof x === 'object' ? {'':'ul', c:Object.keys(x).map(function(key){
		return{'':'li',c:[{'':'em', c:JSON.stringify(key)}, convert(x[key], !key || key.endsWith('_'))]};
	})} :
	typeof x === 'string' && isLink ? {'':'a', href:x, c:JSON.stringify(x)} : JSON.stringify(x)
);
let res = buildDom(convert(data, false));
if (!Array.isArray(res)) {res = [res];}
for (let el of res) {document.body.appendChild(el)}
})();
