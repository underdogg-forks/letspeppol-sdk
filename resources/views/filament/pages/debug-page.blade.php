<x-filament-panels::page>
    <div class="fi-main container mx-auto py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            <!-- Column 1 -->
            <div class="space-y-10">
                <h1 class="fi-header text-3xl font-bold">TailwindCSS Debug Page (Nord Theme)</h1>
                <p class="fi-main-text">This page is for visually testing the Nord color palette and utility
                    classes.</p>

                <div class="space-y-4">
                    <h2 class="fi-header text-xl font-semibold mb-2">Buttons</h2>
                    <button class="fi-btn fi-btn-primary">Primary</button>
                    <button class="fi-btn fi-btn-secondary">Secondary</button>
                    <button class="fi-btn fi-btn-success">Success</button>
                    <button class="fi-btn fi-btn-danger">Danger</button>
                </div>

                <div class="space-y-4">
                    <h2 class="fi-header text-xl font-semibold mb-2">Alerts</h2>
                    <div class="fi-alert fi-alert-info">Info alert</div>
                    <div class="fi-alert fi-alert-success">Success alert</div>
                    <div class="fi-alert fi-alert-warning">Warning alert</div>
                    <div class="fi-alert fi-alert-danger">Error alert</div>
                </div>

                <div class="space-y-4">
                    <h2 class="fi-header text-xl font-semibold mb-2">Badges</h2>
                    <div class="fi-badges-row">
                        <span class="fi-badge fi-badge-primary">Primary</span>
                        <span class="fi-badge fi-badge-success">Success</span>
                        <span class="fi-badge fi-badge-warning">Warning</span>
                        <span class="fi-badge fi-badge-danger">Danger</span>
                    </div>
                </div>

                <div class="space-y-4">
                    <h2 class="fi-header text-xl font-semibold mb-2">Typography</h2>
                    <h3 class="fi-header text-lg font-bold">Heading 3</h3>
                    <p class="text-base fi-main-text">This is a normal paragraph. <span
                            class="font-semibold">Bold text</span> and
                        <span class="italic">italic text</span>.</p>
                    <ul class="list-disc pl-5 text-sm fi-main-text" style="color: var(--color-polarnight-500)">
                        <li>List item one</li>
                        <li>List item two</li>
                    </ul>
                </div>
            </div>

            <!-- Column 2 -->
            <div class="space-y-10">
                <div class="space-y-4">
                    <h2 class="fi-header text-xl font-semibold mb-2">Form Elements</h2>
                    <form class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium fi-main-text">Text Input</label>
                            <input type="text" class="fi-input" placeholder="Type here...">
                        </div>
                        <div>
                            <label class="block text-sm font-medium fi-main-text">Select</label>
                            <select class="fi-input">
                                <option>Option 1</option>
                                <option>Option 2</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="checkbox1" class="fi-checkbox">
                            <label for="checkbox1" class="text-sm fi-main-text">Checkbox</label>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="radio" id="radio1" name="radio" class="fi-radio">
                            <label for="radio1" class="text-sm fi-main-text">Radio 1</label>
                            <input type="radio" id="radio2" name="radio" class="fi-radio">
                            <label for="radio2" class="text-sm fi-main-text">Radio 2</label>
                        </div>
                        <div>
                            <label class="block text-sm font-medium fi-main-text">Textarea</label>
                            <textarea class="fi-input" rows="3"></textarea>
                        </div>
                        <button type="submit" class="fi-btn fi-btn-primary">Submit</button>
                    </form>
                </div>

                <div class="space-y-4">
                    <h2 class="fi-header text-xl font-semibold mb-2">Table</h2>
                    <div class="overflow-x-auto">
                        <table class="fi-table min-w-full text-sm">
                            <thead>
                            <tr>
                                <th class="fi-table-header">Name</th>
                                <th class="fi-table-header">Role</th>
                                <th class="fi-table-header">Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr class="fi-table-row-odd">
                                <td class="fi-table-cell">Alice</td>
                                <td class="fi-table-cell">Admin</td>
                                <td class="fi-table-cell"><span class="fi-badge fi-badge-active">Active</span></td>
                            </tr>
                            <tr class="fi-table-row-even">
                                <td class="fi-table-cell">Bob</td>
                                <td class="fi-table-cell">User</td>
                                <td class="fi-table-cell"><span class="fi-badge fi-badge-warning">Pending</span></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="space-y-4">
                    <h2 class="fi-header text-xl font-semibold mb-2">Card Example</h2>
                    <div class="fi-card">
                        <h3 class="fi-header font-bold text-lg mb-2">Card Title</h3>
                        <p class="fi-main-text">This is a card with some content. Use this to test box shadows, padding,
                            and rounded corners.</p>
                        <button class="fi-btn fi-btn-primary mt-3">Action</button>
                    </div>
                </div>

                <!-- Common Heroicons Section -->
                <div class="space-y-4">
                    <h2 class="fi-header text-xl font-semibold mb-2">Common Heroicons</h2>
                    <div class="flex flex-wrap gap-6 items-center">
                        <div class="flex flex-col items-center">
                            <x-filament::icon name="heroicon-o-x-mark" class="w-8 h-8 fi-icon-danger" />
                            <span class="mt-1 text-xs fi-main-text">X (Close)</span>
                        </div>
                        <div class="flex flex-col items-center">
                            <x-filament::icon name="heroicon-o-paper-airplane" class="w-8 h-8 fi-icon-primary" />
                            <span class="mt-1 text-xs fi-main-text">Paper Airplane (Send)</span>
                        </div>
                        <div class="flex flex-col items-center">
                            <x-filament::icon name="heroicon-o-arrow-down-tray" class="w-8 h-8 fi-icon-success" />
                            <span class="mt-1 text-xs fi-main-text">Arrow Down Tray (Download)</span>
                        </div>
                        <div class="flex flex-col items-center">
                            <x-filament::icon name="heroicon-o-arrow-down-tray" class="w-8 h-8 fi-icon-secondary" />
                            <span class="mt-1 text-xs fi-main-text">Floppy Disk (Save)</span>
                        </div>
                    </div>
                </div>
                <!-- End Common Heroicons Section -->

                <!-- Direct Blade Heroicons Test Section -->
                <div class="space-y-4">
                    <h2 class="fi-header text-xl font-semibold mb-2">Direct Blade Heroicons Test</h2>
                    <div class="flex flex-wrap gap-6 items-center">
                        <div class="flex flex-col items-center">
                            <x-heroicon-o-x-mark class="w-8 h-8 fi-icon-danger" />
                            <span class="mt-1 text-xs fi-main-text">X (Close) - Direct</span>
                        </div>
                        <div class="flex flex-col items-center">
                            <x-heroicon-o-paper-airplane class="w-8 h-8 fi-icon-primary" />
                            <span class="mt-1 text-xs fi-main-text">Paper Airplane (Send) - Direct</span>
                        </div>
                        <div class="flex flex-col items-center">
                            <x-heroicon-o-arrow-down-tray class="w-8 h-8 fi-icon-success" />
                            <span class="mt-1 text-xs fi-main-text">Arrow Down Tray (Download) - Direct</span>
                        </div>
                        <div class="flex flex-col items-center">
                            <x-heroicon-o-arrow-down-tray class="w-8 h-8 fi-icon-secondary" />
                            <span class="mt-1 text-xs fi-main-text">Floppy Disk (Save) - Direct</span>
                        </div>
                    </div>
                </div>
                <!-- End Direct Blade Heroicons Test Section -->
            </div>
        </div>
    </div>
</x-filament-panels::page>

