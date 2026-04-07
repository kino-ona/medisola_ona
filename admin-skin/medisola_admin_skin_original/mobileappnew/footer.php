<?php
/**
 * 관리자 하단
 *
 * @copyright ⓒ 2016, NHN godo: Corp.
 * @link      http://www.godo.co.kr
 * @author    Shin Donggyu <artherot@godo.co.kr>
 */
?>
<?php include($layoutLayerManager); ?>
<footer class="text-center">
    <div class="mgb5" style="margin: 0 10px 0 10px;">
        <!--button-- class="btn btn-md btn-block more border-r-n copyright-button">카피라이트 정보</button-->
        <p class="mgb5">
            ⓒ NHN COMMERCE<span class="text-primary" style="font-weight:700;color: #F91D11;">:</span> Corp. All Rights Reserved.
        </p>
        <p><small class="badge badge-sm">version <script>var version = navigator.userAgent.match(/appVersion\/(.*?)\s/);document.write(version[1]);</script></small></p>
    </div>
</footer>
<div id="top-anchor" class="h-skip" style=""><a href="#top">TOP</a></div>
<div id="loader"></div>
<div class="debug">
</div>
</div><!-- /#wrapper -->
<div id="app-download"></div>
<script type="text/javascript">
    <!--
    $(document).ready(function () {
        <?= $functionAuth; ?>
    });
    //-->
</script>
