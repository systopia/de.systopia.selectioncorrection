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
    <div class="label">{$form.group_title.label}</div>
    <div class="content">{$form.group_title.html}</div>
  </div>
{elseif $current_page == 'contact_person_definition'}
  <div>
    <h3>{ts}Contact person selection:{/ts}</h3>
    <br>
  </div>
  {if $contact_person_definition_element_organisation_map}
    {foreach key=element_identifier item=organisation_id from=$contact_person_definition_element_organisation_map}
      <div>
        <div class="label">{$form.$element_identifier.label}</div>
        <br>
        <div class="content">{$form.$element_identifier.html}</div>
        <br>
      </div>
    {/foreach}
  {else}
    <div>
      {ts}There were no organisations found. Click the button to continue.{/ts}
      <br><br>
    </div>
  {/if}
{/if}

{include file="CRM/common/formButtons.tpl" location="bottom"}
