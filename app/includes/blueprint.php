<?php
/**
 * BACKGROUND BLUEPRINT
 * --------------------
 * Purely decorative (aria-hidden, pointer-events: none): a slowly
 * rotating wireframe shield with a padlock, dimension lines and
 * coordinate labels, shown behind the page content.
 * Markup/CSS adapted from the "Blueprint" theme sample. Styles live in
 * the "BACKGROUND BLUEPRINT" block of assets/css/style.css.
 */
?>
<div class="bp-stage" aria-hidden="true">
    <div class="blueprint-stage">
        <!-- Dimension lines -->
        <div class="dim-line h-top"></div>
        <div class="dim-line h-bottom"></div>
        <div class="dim-line v-left"></div>
        <div class="dim-line v-right"></div>

        <!-- Ticks -->
        <div class="dim-tick t1"></div>
        <div class="dim-tick t2"></div>
        <div class="dim-tick t3"></div>
        <div class="dim-tick t4"></div>
        <div class="dim-tick t5"></div>
        <div class="dim-tick t6"></div>
        <div class="dim-tick t7"></div>
        <div class="dim-tick t8"></div>

        <!-- Dimension labels -->
        <div class="dim-label h-top-label">480.00</div>
        <div class="dim-label h-bottom-label">REF // 048</div>
        <div class="dim-label v-left-label">240.00</div>
        <div class="dim-label v-right-label">SEC // 07</div>

        <!-- Wireframe guides -->
        <div class="wire w1"></div>
        <div class="wire w2"></div>
        <div class="wire w3"></div>
        <div class="wire w4"></div>

        <!-- 3D shield / lock object -->
        <div class="object-container">
            <div class="shield">
                <div class="face"></div>
                <div class="face"></div>
                <div class="face"></div>
                <div class="face"></div>
                <div class="face"></div>
                <div class="face"></div>
                <div class="face"></div>
                <div class="face"></div>
                <div class="face"></div>
            </div>

            <!-- Lock icon on front -->
            <div class="lock">
                <div class="lock-body"></div>
            </div>
        </div>

        <!-- Crosshair -->
        <div class="crosshair"></div>

        <!-- Object label -->
        <div class="obj-label">
            <span>AVALON-VAULT &middot; SHIELD_LOCK.STEP</span>
            <span class="id">REV 03 / SCALE 1:1</span>
        </div>
    </div>
</div>
