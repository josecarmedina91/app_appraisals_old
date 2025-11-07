<?php
include_once('script.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .auto-resize {
            overflow: hidden;
            resize: none;
        }
    </style>
</head>

<body class="bg-gradient-to-r from-blue-50 to-blue-100 min-h-screen flex items-center justify-center p-6">
    <div class="container mx-auto bg-white shadow-lg rounded-lg p-8">
        <a href="../overview_inspection.php?id=<?php echo urlencode($inspectionId); ?>" class="inline-block mb-4 p-2 bg-blue-500 text-white rounded-full hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-lg">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">Inspection Details</h2>
        <h3 class="text-2xl font-semibold text-gray-600 mb-4 border-b pb-3">Photo Consent Form</h3>
        <form class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php
            function renderInput($label, $value, $url)
            {
                echo "<div>
                        <label class='block font-semibold text-gray-700 text-lg flex items-center'>$label</label>
                        <a href='$url'>
                            <input type='text' value='" . htmlspecialchars($value) . "' readonly class='auto-resize border p-2 rounded bg-gray-100 w-full mt-1'>
                        </a>
                      </div>";
            }
            renderInput('Vacant Property', $vacant_property ? 'Yes' : 'No', "../photo_consent.php?id=" . urlencode($inspectionId));
            renderInput('Consent Photos', $consent_photos ? 'Yes' : 'No', "../photo_consent.php?id=" . urlencode($inspectionId));
            renderInput('Consent Photos Exception', $consent_photos_exception ? 'Yes' : 'No', "../photo_consent.php?id=" . urlencode($inspectionId));
            ?>

            <div class="md:col-span-2">
                <label class="block font-semibold text-gray-700 text-lg flex items-center">Exception Details</label>
                <a href="../photo_consent.php?id=<?php echo urlencode($inspectionId); ?>">
                    <textarea readonly class="auto-resize border p-2 rounded bg-gray-100 w-full mt-1" rows="3"><?php echo htmlspecialchars($exception_details); ?></textarea>
                </a>
            </div>

            <?php
            renderInput('Occupant Name', $occupant_name, "../photo_consent.php?id=" . urlencode($inspectionId));
            renderInput('Occupant Type', $occupant_type, "../photo_consent.php?id=" . urlencode($inspectionId));
            renderInput('Inspection Date', $date, "../photo_consent.php?id=" . urlencode($inspectionId));
            renderInput('Signature Provided', $signature_requested ? 'Yes' : 'No', "../photo_consent.php?id=" . urlencode($inspectionId));
            ?>

            <h3 class="text-2xl font-semibold text-gray-600 mb-4 border-b pb-3 mt-8 md:col-span-2">Exterior Inspection Details</h3>

            <?php
            function renderInputWithIcon($label, $value, $url, $iconUrl)
            {
                echo "<div>
                        <label class='block font-semibold text-gray-700 text-lg flex items-center space-x-2'>
                            <a href='$iconUrl' class='flex items-center space-x-2'>
                                <span>$label</span>
                                <svg class='h-6 w-6 text-blue-500 ml-2' width='24' height='24' viewBox='0 0 24 24' stroke-width='2' stroke='currentColor' fill='none' stroke-linecap='round' stroke-linejoin='round'>
                                    <path stroke='none' d='M0 0h24v24H0z' />
                                    <circle cx='12' cy='13' r='3' />
                                    <path d='M5 7h1a2 2 0 0 0 2 -2a1 1 0 0 1 1 -1h2m9 7v7a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-9a2 2 0 0 1 2 -2' />
                                    <line x1='15' y1='6' x2='21' y2='6' />
                                    <line x1='18' y1='3' x2='18' y2='9' />
                                </svg>
                            </a>
                        </label>
                        <a href='$url'>
                            <input type='text' value='" . htmlspecialchars($value) . "' readonly class='auto-resize border p-2 rounded bg-gray-100 w-full mt-1'>
                        </a>
                      </div>";
            }
            renderInputWithIcon('Exterior Finish', $exterior_finish, "../exterior_overview_exterior.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Exterior Finish'));
            renderInputWithIcon('Type of Building', $type_of_building, "../exterior_overview_type_of_bu.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Type of Building'));
            renderInputWithIcon('Roof', $roof, "../exterior_overview_roof.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Roof'));
            renderInputWithIcon('Garage', $garage, "../exterior_overview_gar_car.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Garage'));
            ?>

            <div class="md:col-span-2">
                <label class="block font-semibold text-gray-700 text-lg flex items-center space-x-2">
                    <a href="../component/gallery.php?id=<?php echo urlencode($inspectionId); ?>&titulo=<?php echo urlencode('Damage'); ?>" class="flex items-center space-x-2">
                        <span>Damage</span>
                        <svg class="h-6 w-6 text-blue-500 ml-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24V24H0z" />
                            <circle cx="12" cy="13" r="3" />
                            <path d="M5 7h1a2 2 0 0 0 2 -2a1 1 0 0 1 1 -1h2m9 7v7a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-9a2 2 0 0 1 2 -2" />
                            <line x1="15" y1="6" x2="21" y2="6" />
                            <line x1="18" y1="3" x2="18" y2="9" />
                        </svg>
                    </a>
                </label>
                <a href="../exterior_overview_damage.php?id=<?php echo urlencode($inspectionId); ?>">
                    <textarea readonly class="auto-resize border p-2 rounded bg-gray-100 w-full mt-1" rows="3"><?php echo htmlspecialchars($damage); ?></textarea>
                </a>
            </div>

            <h3 class="text-2xl font-semibold text-gray-600 mb-4 border-b pb-3 mt-8 md:col-span-2">Interior Inspection Details</h3>

            <?php
            renderInputWithIcon('Windows', $windows, "../interior_overview_windows.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Windows'));
            renderInputWithIcon('Style', $style, "../interior_overview_style.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Style'));
            renderInputWithIcon('Interior Finish', $interior_finish, "../interior_overview_interior.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Interior Finish'));
            renderInputWithIcon('Construction', $construction, "../interior_overview_construction.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Construction'));
            renderInputWithIcon('Foundation', $foundation, "../interior_overview_foundation.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Foundation'));
            renderInputWithIcon('Insulation', $insulation, "../interior_overview_insulation.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Insulation'));
            renderInputWithIcon('Closets', $closets, "../interior_overview_closets.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Closets'));
            renderInputWithIcon('Plumbing Lines', $plumbing_lines, "../interior_overview_plulin.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Plumbing Lines'));
            renderInputWithIcon('Electrical', $electrical, "../interior_overview_electrical.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Electrical'));
            renderInputWithIcon('Heating System', $heating_system, "../interior_overview_heasys.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Heating System'));
            renderInputWithIcon('Water Heater', $water_heater, "../interior_overview_wathea.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Water Heater'));
            renderInputWithIcon('Flooring', $flooring, "../interior_overview_flooring.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Flooring'));
            renderInputWithIcon('Floor Plan', $floor_plan, "../interior_overview_floor_plan.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Floor Plan'));
            renderInputWithIcon('Counter Tops', $counter_tops, "../interior_overview_counter_tops.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Counter Tops'));
            renderInputWithIcon('Built Ins Extras', $built_ins_extras, "../interior_overview_built_ins_extras.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Built_ins_extras'));
            ?>

            <div class="md:col-span-2">
                <label class="block font-semibold text-gray-700 text-lg flex items-center space-x-2">
                    <a href="../component/gallery.php?id=<?php echo urlencode($inspectionId); ?>&titulo=<?php echo urlencode('Overall in condition'); ?>" class="flex items-center space-x-2">
                        <span>Overall In Condition</span>
                        <svg class="h-6 w-6 text-blue-500 ml-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24V24H0z" />
                            <circle cx="12" cy="13" r="3" />
                            <path d="M5 7h1a2 2 0 0 0 2 -2a1 1 0 0 1 1 -1h2m9 7v7a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-9a2 2 0 0 1 2 -2" />
                            <line x1="15" y1="6" x2="21" y2="6" />
                            <line x1="18" y1="3" x2="18" y2="9" />
                        </svg>
                    </a>
                </label>
                <a href="../interior_overview_overcon.php?id=<?php echo urlencode($inspectionId); ?>">
                    <textarea readonly class="auto-resize border p-2 rounded bg-gray-100 w-full mt-1" rows="3"><?php echo htmlspecialchars($overall_in_condition); ?></textarea>
                </a>
            </div>

            <div class="md:col-span-2">
                <label class="block font-semibold text-gray-700 text-lg flex items-center space-x-2">
                    <a href="../component/gallery.php?id=<?php echo urlencode($inspectionId); ?>&titulo=<?php echo urlencode('Under construction or renovation'); ?>" class="flex items-center space-x-2">
                        <span>Under Construction or Renovation</span>
                        <svg class="h-6 w-6 text-blue-500 ml-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24V24H0z" />
                            <circle cx="12" cy="13" r="3" />
                            <path d="M5 7h1a2 2 0 0 0 2 -2a1 1 0 0 1 1 -1h2m9 7v7a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-9a2 2 0 0 1 2 -2" />
                            <line x1="15" y1="6" x2="21" y2="6" />
                            <line x1="18" y1="3" x2="18" y2="9" />
                        </svg>
                    </a>
                </label>
                <a href="../interior_overview_under_conren.php?id=<?php echo urlencode($inspectionId); ?>">
                    <textarea readonly class="auto-resize border p-2 rounded bg-gray-100 w-full mt-1 text-sm" rows="3"><?php echo str_replace('%,', "%\n", htmlspecialchars($under_construction_or_renovation)); ?></textarea>
                </a>
            </div>

            <h3 class="text-2xl font-semibold text-gray-600 mb-4 border-b pb-3 mt-8 md:col-span-2">Basement</h3>

            <?php
            renderInputWithIcon('Basement', $basement, "../interior_overview_bsmt.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Basement'));
            renderInputWithIcon('Basement Levels', $basement_levels, "../interior_overview_bsmt.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Basement Levels'));
            renderInputWithIcon('Separate Entrance', $basement_separate_entrace, "../interior_overview_bsmt.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Separate Entrance'));
            renderInputWithIcon('Basement Use', $basement_use, "../interior_overview_bsmt.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Basement Use'));
            renderInputWithIcon('Basement Area', $basement_area . " %", "../interior_overview_bsmt.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Basement Area'));
            renderInputWithIcon('Basement Finished', $basement_finished . " %", "../interior_overview_bsmt.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Basement Finished'));
            ?>

            <div class="md:col-span-2">
                <label class="block font-semibold text-gray-700 text-lg flex items-center">Room Allocation</label>
                <?php foreach ($room_allocations as $level => $rooms) : ?>
                    <div class="mt-4">
                        <h4 class="font-bold text-base"><?php echo htmlspecialchars($level); ?></h4>
                        <table class="min-w-full bg-white border border-gray-300 mt-2">
                            <thead>
                                <tr>
                                    <th class="py-2 px-4 border-b border-gray-300 text-center border-r w-1/2">Room</th>
                                    <th class="py-2 px-4 border-b border-gray-300 text-center">Number</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rooms as $room => $number) : ?>
                                    <tr>
                                        <td class="py-2 px-4 border-b border-gray-300 text-center"><?php echo htmlspecialchars($room); ?></td>
                                        <td class="py-2 px-4 border-b border-gray-300 text-center"><?php echo htmlspecialchars($number); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="md:col-span-2">
                <label class="block font-semibold text-gray-700 text-lg flex items-center">Basement Room Allocation</label>
                <?php foreach ($basement_room_allocations as $level => $rooms) : ?>
                    <div class="mt-4">
                        <h4 class="font-bold text-base"><?php echo htmlspecialchars($level); ?></h4>
                        <table class="min-w-full bg-white border border-gray-300 mt-2">
                            <thead>
                                <tr>
                                    <th class="py-2 px-4 border-b border-gray-300 text-center border-r w-1/2">Room</th>
                                    <th class="py-2 px-4 border-b border-gray-300 text-center">Number</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rooms as $room => $number) : ?>
                                    <tr>
                                        <td class="py-2 px-4 border-b border-gray-300 text-center"><?php echo htmlspecialchars($room); ?></td>
                                        <td class="py-2 px-4 border-b border-gray-300 text-center"><?php echo htmlspecialchars($number); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>

            <h3 class="text-2xl font-semibold text-gray-600 mb-4 border-b pb-3 mt-8 md:col-span-2">Site Inspection Details</h3>

            <?php
            renderInputWithIcon('Driveway', $driveway, "../site_overview_driveway.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Driveway'));
            renderInput('Select Option Driveway', $select_option_driveway, "../site_overview_driveway.php?id=" . urlencode($inspectionId));
            renderInputWithIcon('Parking', $parking, "../site_overview_parking.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Parking'));
            renderInputWithIcon('Electrical', $electrical_site, "../site_overview_electrical.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Electrical'));
            renderInputWithIcon('Utilities', $utilities, "../site_overview_utilities.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Utilities'));
            renderInputWithIcon('Features', $features, "../site_overview_features.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Features'));
            renderInputWithIcon('Curb Appeal', $curb_appeal, "../site_overview_curb_appeal.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Curb appeal'));
            renderInputWithIcon('Topography', $topography, "../site_overview_topography.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Topography'));
            renderInputWithIcon('Landscaping', $landscaping, "../site_overview_landscaping.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Landscaping'));
            renderInputWithIcon('Site Improvements', $site_improvements, "../site_overview_site_improvements.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Site improvements'));
            renderInputWithIcon('Site Features', $site_features, "../site_overview_site_features.php?id=" . urlencode($inspectionId), "../component/gallery.php?id=" . urlencode($inspectionId) . "&titulo=" . urlencode('Site Features'));
            ?>
        </form>
        <div>
            <button type="button" class="mt-6 w-full p-2 bg-green-500 text-white rounded-lg hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 shadow-lg" onclick="openSignatureModal()">Certify & Submit Inspection Report</button>
        </div>
    </div>
    <div id="signature-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-4 rounded-lg shadow-lg max-w-lg w-full max-h-screen h-5/6 flex flex-col">
            <h2 class="text-xl font-bold mb-4">Draw your signature</h2>
            <p>Inspection Date: <?php echo date("Y-m-d"); ?></p>
            <p>Inspector Name: <?php echo htmlspecialchars($nombre_usuario); ?></p>
            <canvas id="signature-pad" class="border w-full h-64 mb-4 flex-grow"></canvas>
            <div class="mt-auto flex justify-end space-x-4 w-full">
                <button type="button" class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 shadow-lg w-1/2" onclick="closeSignatureModal()">Cancel</button>
                <button type="button" class="p-2 bg-green-500 text-white rounded-lg hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 shadow-lg w-1/2" onclick="confirmSignature()">Certify & Submit</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script>
        const inspectionId = "<?php echo urlencode($inspectionId); ?>";
    </script>
    <script src="script.js"></script>
</body>

</html>