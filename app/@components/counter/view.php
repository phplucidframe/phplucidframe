<h3><?php echo $title ?></h3>

<div class="table">
    <div class="row">
        <label class="label">Counter: <strong><?php echo $count ?></strong></label>
        <div>
            <!-- On click, trigger the live action "decrement" to decrease the count -->
            <button type="button" class="button red" data-live="decrement">Decrement--</button>
            <!-- On click, trigger the live action "increment" to increase the count -->
            <button type="button" class="button green" data-live="increment">Increment++</button>
        </div>
    </div>
</div>
