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

{if $current_page == $preselection_page_name}
  {foreach from=$filter_identifiers item=identifier}
    <div class="crm-section">
      <div class="label">{$form.$identifier.label}</div>
      <div class="content">{$form.$identifier.html}</div>
      <div class="clear"></div>
    </div>
  {/foreach}
{else}
{/if}

{include file="CRM/common/formButtons.tpl" location="bottom"}
