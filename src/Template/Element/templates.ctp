<template id="datatable-template">
    <div class="datatable" :class="{'datatable-responsive': isMobile}">
        <div class="datatable-loading">
            <div class="backdrop"></div>
            <div class="loading-text">Caricamento</div>
        </div>
        <table class="table table-hover table-bordered">
            <thead>
                <tr>
                    <td :colspan="tableColumnCount" class="massive-actions-container">
                        <div class="left-actions">
                            <div class="show-per-page">
                                <small>
                                    Mostra
                                    <select class="form-control input-xs" v-model="limit">
                                        <option v-for="p in [10, 20, 50, 100]" :value="p">{{ p }}</option>
                                    </select>
                                    per pagina
                                </small>
                            </div>
                            <div class="selection-actions" v-if="hasMassiveActions">
                                <small>
                                    <a href="Javascript:void(0)" @click="selectVisible">Seleziona Tutto</a>
                                </small>
                                <small>
                                    <a href="Javascript:void(0)" @click="unselectVisible">Deseleziona Tutto</a>
                                </small>
                                <small class="count-checked-rows" v-if="rowsChecked.length">
                                    Selezionato {{ rowsChecked.length }} di {{ pagination.count }}
                                </small>
                            </div>
                        </div>
                        <div class="massive-actions" v-if="hasMassiveActions">
                            <select class="form-control input-xs" v-model="currentMassiveAction">
                                <option value=""></option>
                                <option v-for="massiveActionConfig, massiveAction in config.massive_actions" :value="massiveAction">
                                    {{ massiveActionConfig.title }}
                                </option>
                            </select>
                            <button type="button" class="btn btn-primary btn-xs" :disabled="currentMassiveAction == null || !currentMassiveAction.length || rowsChecked.length == 0? true : false" @click="doMassiveAction">Esegui</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th v-if="hasMassiveActions"></th>
                    <th v-for="columnData, columnName in config.columns" v-show="showOnMobile(columnName)">
                        <a href="Javascript:void(0)" v-if="isColumnSortable(columnName)" @click="sortBy(columnName)">
                            {{ columnData.header }}
                            <span v-if="isCurrentSortColumn(columnName)" class="sort-dir">
                                {{ sort_direction == 'asc' ? '&darr;' : '&uarr;' }}
                            </span>
                        </a>
                        <span v-else>
                            {{ columnData.header }}
                        </span>
                    </th>
                    <th v-if="config.has_actions"></th>
                </tr>
                <tr>
                    <th v-if="hasMassiveActions"></th>
                    <th v-for="columnData, columnName in config.columns" v-show="showOnMobile(columnName)">
                        <template v-if="isColumnFilterable(columnName) && config.filters[columnName]">
                            <div :is="config.filters[columnName] + 'Filter'" :column="columnName" :config="config" ref="filter"></div>
                        </template>
                    </th>
                    <th v-if="config.has_actions"></th>
                </tr>
                <tr>
                    <th :colspan="tableColumnCount">
                        <a href="Javascript:void(0)" class="pull-left btn btn-primary btn-xs" v-if="isMobile" @click="showMoreFilters"><i class="fa fa-search"></i></a>
                        <div class="btn-group pull-right">
                            <button class="btn btn-primary btn-xs" type="button" @click="filter">Filtra</button>
                            <a href="Javascript:void(0)" class="btn btn-xs" @click="resetFilter">Reset</a>
                        </div>
                        <div class="more-filters" v-show="false" v-if="isMobile">
                            <div><b>Filtri aggiuntivi</b></div>
                            <table class="table table-striped">
                                <tbody>
                                    <tr v-for="columnData, columnName in config.columns" v-if="!showOnMobile(columnName) && isColumnFilterable(columnName) && config.filters[columnName]">
                                        <td><b>{{ columnData.header }}</b></td>
                                        <td>
                                            <div :is="config.filters[columnName] + 'Filter'" :column="columnName" :config="config" ref="filter"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="rows.length == 0">
                    <td :colspan="tableColumnCount" class="text-center">
                        Nessun dato trovato
                    </td>    
                </tr>
                <template v-else v-for="row in rows">
                    <tr @click="showRowDetail">
                        <td v-if="hasMassiveActions" class="text-center">
                            <input type="checkbox" :value="row.id" v-model="rowsChecked"/>
                        </td>
                        <td v-for="columnData, columnName in config.columns" v-show="showOnMobile(columnName)">
                            <template v-if="columnData.renderer">
                               <span v-html="row.columns[columnName]"></span>
                            </template>
                            <template v-else>
                                {{ row.columns[columnName] }}
                            </template>
                        </td>
                        <td class="text-center" v-if="config.has_actions">
                            <div class="btn-group">
                                <template v-if="row.actions.length && !isMobile">
                                    <a v-if="row.actions[0].type == 'get'" :href="row.actions[0].url" class="btn btn-sm" :class="['row-action-' + row.actions[0].code]">{{ row.actions[0].title }}</a>
                                    <a v-else href="Javascript:void(0)" @click="doPostAction(row.actions[0].url, row.actions[0].confirm)" class="btn btn-sm" :class="['row-action-' + row.actions[0].code]">{{ row.actions[0].title }}</a>
                                </template>
                                <template v-if="row.actions.length > (isMobile ? 0 : 1)">
                                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle btn-sm" data-toggle="dropdown">
                                        <span class="caret"></span>
                                        <span class="sr-only">Toggle Dropdown</span>
                                    </button>
                                    <ul class="dropdown-menu" role="menu"> 
                                        <template v-for="action, index in row.actions">
                                            <template v-if="index >= (isMobile ? 0 : 1)">
                                                <li>
                                                    <a v-if="action.type == 'get'" :href="action.url" :class="['row-action-' + action.code]">{{ action.title }}</a>
                                                    <a v-else href="Javascript:void(0)" @click="doPostAction(action.url, action.confirm)" :class="['row-action-' + action.code]">{{ action.title }}</a>
                                                </li>
                                            </template>
                                        </template>
                                    </ul>
                                </template>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="isMobile && needAdditionalInfo" v-show="false" class="detail-row">
                        <td :colspan="tableColumnCount">
                            <table class="table table-striped">
                                <tbody>
                                    <tr v-for="columnData, columnName in config.columns" v-if="!showOnMobile(columnName)">
                                        <td>
                                            <b>{{ columnData.header }}</b>
                                        </td>
                                        <td>
                                            <template v-if="columnData.renderer">
                                               <span v-html="row.columns[columnName]"></span>
                                            </template>
                                            <template v-else>
                                                {{ row.columns[columnName] }}
                                            </template>
                                        </td>
                                    </tr> 
                                <tbody>
                            </table>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
        <div class="paginator" v-if="pagination">
            <ul class="pagination">
                <li class="prev" :class="[pagination.pageCount > 1 ? '' : 'disabled']">
                    <a href="Javascript:void(0)" @click="goToPage(1)" v-if="pagination.pageCount > 1">
                        <i class="fa fa-angle-double-left"></i>
                    </a>
                    <a v-else href="Javascript:void(0)" onclick="return false;">
                        <i class="fa fa-angle-double-left"></i>
                    </a>
                </li>
                <li class="prev" :class="[pagination.prevPage ? '' : 'disabled']">
                    <a href="Javascript:void(0)" @click="goToPage(pagination.page - 1)" v-if="pagination.prevPage">
                        <i class="fa fa-angle-left"></i>
                    </a>
                    <a v-else href="Javascript:void(0)" onclick="return false;">
                        <i class="fa fa-angle-left"></i>
                    </a>
                </li>
                <li v-for="page in pagination.pageCount" :class="[page == pagination.page ? 'active' : '']">
                    <a href="Javascript:void(0)" @click="goToPage(page)">{{ page }}</a>
                </li>
                <li class="next" :class="[pagination.nextPage ? '' : 'disabled']">
                    <a href="Javascript:void(0)" @click="goToPage(pagination.page + 1)" v-if="pagination.nextPage">
                        <i class="fa fa-angle-right"></i>
                    </a>
                    <a v-else href="Javascript:void(0)" onclick="return false;">
                        <i class="fa fa-angle-right"></i>
                    </a>
                </li>
                <li class="prev" :class="[pagination.pageCount > 1 ? '' : 'disabled']">
                    <a href="Javascript:void(0)" @click="goToPage(pagination.pageCount)" v-if="pagination.pageCount > 1">
                        <i class="fa fa-angle-double-right"></i>
                    </a>
                    <a v-else href="Javascript:void(0)" onclick="return false;">
                        <i class="fa fa-angle-double-right"></i>
                    </a>
                </li>
            </ul>
            <p>{{ pagination.page }} di {{ pagination.pageCount }}, {{ pagination.count }} elementi totali</p>
        </div>
    
    </div>
</template>

<template id="text-filter-template">
    <div class="text-filter">
        <input :type="getFieldType()" class="form-control" :placeholder="'filtra per ' + getColumnName()" ref="value" />
    </div>
</template>

<template id="range-filter-template">
    <div class="range-filter">
        <div>
            Da <input :type="getFieldType()" class="form-control" :placeholder="'filtra da ' + getColumnName()" ref="from" />
        </div>
        <div>
            A<input :type="getFieldType()" class="form-control" :placeholder="'filtra a ' + getColumnName()" ref="to" />
        </div>
    </div>
</template>

<template id="select-filter-template">
    <div class="select-filter">
        <select class="form-control select-2" ref="value">
            <option value=""></option>
            <template v-for="label, value in getOptions()">
                <template v-if="typeof(label) == 'object'">
                    <optgroup :label="value">
                        <option v-for="sub_label, sub_value in label" :value="sub_value">{{ sub_label }}</option>
                    </optgroup>
                </template>
                <option v-else :value="value">{{ label }}</option>
            </template>
        </select>
    </div>
</template>


<?php
    $this->Html->script('Datatable.components.js', ['block' => true]);
?>
