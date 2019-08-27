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
  <div class="crm-section">
    <div class="label">{$form.filter_1.label}</div>
    <div class="content">{$form.filter_1.html}</div>
    <div class="clear"></div>
  </div>
{else}
{/if}

{include file="CRM/common/formButtons.tpl" location="bottom"}
