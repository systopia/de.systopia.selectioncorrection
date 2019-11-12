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

{crmScope extensionKey='de.systopia.selectioncorrection'}

  {if $current_page == 'preselection'}
    {foreach from=$filter_identifiers item=identifier}
      <div class="crm-section">
        <div class="label">{$form.$identifier.label}</div>
        <div class="content">{$form.$identifier.html}</div>
        <div class="clear"></div>
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
    {if $contact_person_definition_element_organisation_map}
      <div>
        <div class="label">
          {ts}Quick setting{/ts}
        </div>
        <div class="content">
          <br>
          <span id="contact_person_quick_setting_all" class="crm-button contact-person-button">
            <input value="{ts}Set all contact persons and organisations.{/ts}" type="button" class="crm-form-submit cancel">
          </span>
          <br><br><br>
          <span id="contact_person_quick_setting_contact_persons_only" class="crm-button contact-person-button">
            <input value="{ts}Set all contact persons.{/ts}" type="button" class="crm-form-submit cancel">
          </span>
          <br><br><br>
          <span id="contact_person_quick_setting_organisations_only" class="crm-button contact-person-button">
            <input value="{ts}Set all organisations.{/ts}" type="button" class="crm-form-submit cancel">
          </span>
          <br><br><br>
          <div class="clear"></div>
        </div>
      </div>
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

{/crmScope}
