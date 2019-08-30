{*-------------------------------------------------------+
| SYSTOPIA MULTI PURPOSE SELECTION CLEANUPS              |
| Copyright (C) 2019 SYSTOPIA                            |
| Author: B. Zschiedrich (zschiedrich@systopia.de)       |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*}

{if $current_page == 'preselection'}
  {foreach from=$filter_identifiers item=identifier}
    <div class="crm-section">
      <div class="label">{$form.$identifier.label}</div>
      <div class="content">{$form.$identifier.html}</div>
    </div>
  {/foreach}
  <div class="clear"></div>
  <div class="crm-section">
    <div class="label">{$form.relationship_types.label}</div>
    <div class="content">{$form.relationship_types.html}</div>
  </div>
  <div class="clear"></div>
  <div class="crm-section">
    <div class="label">{$form.group_name.label}</div>
    <div class="content">{$form.group_name.html}</div>
  </div>
{else}
  {foreach key=organisation_name item=elements from=$contact_person_definition_organisations_element_list}
    <div class="crm-section">
      {$organisation_name}
      {foreach from=$elements item=identifier}
        <div class="crm-section">
          <div class="label">{$form.$identifier.label}</div>
          <div class="content">{$form.$identifier.html}</div>
          <div class="clear"></div>
        </div>
      {/foreach}
    <div>
    <div class="clear"></div>
  {/foreach}
{/if}

{include file="CRM/common/formButtons.tpl" location="bottom"}
