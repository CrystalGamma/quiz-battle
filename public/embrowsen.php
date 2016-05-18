<!DOCTYPE html>
<meta charset=UTF-8>
<style>
ol,ul{list-style-type:none;margin:0;padding:0;display:inline}
ul::before{content:'{'}
ul::after{content:'}'}
ol::before{content:'['}
ol::after{content:']'}
li:not(:last-of-type)::after{content:','}
li{margin-left:1em}
li em::after{content:':'}
</style>
<script src=/scripts/builddom></script>
<br>
<script src=/scripts/embrowsen data-json="<?= htmlspecialchars($json)?>"></script>
