<!-- docara-variant:base -->
<!-- docara-variant:state.default -->
### Task progress

:::ui.progress-bar
{"value":40,"label":"Upload","text-position":"top"}

:::

<!-- docara-variant:state.success -->
### Finished

:::ui.progress-bar
{"value":100,"label":"Build","status":"success","text-position":"inline-end"}

:::

<!-- docara-variant:state.warning -->
### Threshold reached

:::ui.progress-bar
{"value":83,"label":"Disk","meter":true,"warning-at":80,"error-at":95,"text-position":"top"}

:::

<!-- docara-variant:state.error -->
### Error

:::ui.progress-bar
{"value":60,"label":"Import","status":"error"}

:::

<!-- docara-variant:state.indeterminate -->
### Size not known yet

:::ui.progress-bar
{"label":"Preparing","indeterminate":true}

:::

<!-- docara-variant:state.paused -->
### Paused

:::ui.progress-bar
{"value":35,"label":"Sync","paused":true,"text-position":"top"}

:::

<!-- docara-variant:state.meter -->
### Amount in a range

:::ui.progress-bar
{"value":72,"label":"Quota","meter":true,"inline":true}

:::
