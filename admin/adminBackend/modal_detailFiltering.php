<?php

?>

<div class="modal_header admin_detailFilter_color">
    <img src="../assets/img/sliders.svg">
    <h5 class="modal_title">詳細検索</h5>
</div>

<div class="adminModal_detailFilter_container">
    <form id="filter_detail" action="" method="GET">
        <div class="adminModal_detailFilter_element">
            <label class="adminModal_detailFilter_label">価格(円)</label>
            <div class="adminModal_detailFilter_inputContainer">
                <input class="adminModal_detailFilter_input_num" id="price-min" type="number" name="price-min" placeholder="下限なし">
                <span class="adminModal_detailFilter_tilde">~</span>
                <input class="adminModal_detailFilter_input_num"  id="price-max" type="number" name="price-max" placeholder="上限なし">
            </div>
        </div>
        <div class="adminModal_detailFilter_element">
            <label class="adminModal_detailFilter_label">在庫数</label>
            <div class="adminModal_detailFilter_inputContainer">
                <input class="adminModal_detailFilter_input_num" id="stock-min" type="number" name="stock-min" placeholder="下限なし">
                <span class="adminModal_detailFilter_tilde">~</span>
                <input class="adminModal_detailFilter_input_num" id="stock-max"  type="number" name="stock-max" placeholder="上限なし">
            </div>
        </div>
        <div class="adminModal_detailFilter_element">
            <label class="adminModal_detailFilter_label">登録日</label>
            <div class="adminModal_detailFilter_inputContainer">
                <input class="adminModal_detailFilter_input_date" id="date-min" name="date-min" type="date">
                <span class="adminModal_detailFilter_tilde">~</span>
                <input class="adminModal_detailFilter_input_date" id="date-max" name="date-max" type="date">
            </div>
        </div>
        <div class="adminModal_detailFilter_element">
            <label class="adminModal_detailFilter_label">フリーワード</label>
            <div class="adminModal_detailFilter_inputContainer">
                <input class="adminModal_detailFilter_input_text" id="word" name="word" type="text">
            </div>
        </div>
    </form>
</div>

<div class="modal-detailFiltering-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
    <button form="filter_detail" type="reset" class="btn btn-outline-secondary">クリア</button>
    <button form="filter_detail" type="submit" class="btn btn-primary">この条件で検索</button>
</div>
