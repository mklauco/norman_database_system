<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatLegacy\Reconcile;

use RuntimeException;
use stdClass;

/**
 * Corrects the name of 464 stations in empodat_stations: 446 TerraChem stations
 * (files 632-636) whose legacy name changed after the import of 2026-05-20
 * (country name replaced by the short sample code), and 18 HELCOM stations
 * (files 396, 397) with broken letters. Only `name` is written, and only where it
 * still equals the wrong name; a station already holding the approved name counts
 * as done. Names are compared exactly. A listed station that is missing or holds
 * any other name stops the run before anything is written.
 *
 * Production (dry run = the same without the -e flags):
 *   docker exec -e RECONCILE_MODE=execute -e RECONCILE_EXECUTE=true -e RECONCILE_EXPECT_PG_DATABASE=nds nds-app php artisan db:seed --class='Database\Seeders\EmpodatLegacy\Reconcile\ReconcileStationNamesSeeder' --force
 */
final class ReconcileStationNamesSeeder extends ReconcileSeeder
{
    private const TABLE = 'empodat_stations';

    /**
     * Station id => [current wrong name, approved legacy name].
     *
     * @var array<int, array{0: string, 1: string}>
     */
    private const STATIONS = [
        41401 => ['K├╝dema Bay', 'Küdema Bay'],
        41408 => ['P├Ąrnu Bay', 'Pärnu Bay'],
        41423 => ['V├Ąike-Pakri', 'Väike-Pakri'],
        41424 => ['V├Ąinameri', 'Väinameri'],
        42706 => ['Tv├Ąrminne start', 'Tvärminne start'],
        116114 => ['├ľrefj├Ąrden', 'Örefjärden'],
        116184 => ['Ask├Â', 'Askö'],
        116648 => ['G├Âteborg Hamn', 'Göteborg Hamn'],
        116701 => ['Gr├Ąs├Â', 'Gräsö'],
        116743 => ['Himmerfj├Ąrden', 'Himmerfjärden'],
        116754 => ['Holm├Âarna', 'Holmöarna'],
        116845 => ['Kinnb├Ącksfj├Ąrden', 'Kinnbäcksfjärden'],
        116892 => ['Kv├Ąd├Âfj├Ąrden', 'Kvädöfjärden'],
        117304 => ['Malm├Â Hamn', 'Malmö Hamn'],
        117920 => ['Oxel├Âsund', 'Oxelösund'],
        117951 => ['Ronneby├ąns mynning', 'Ronnebyåns mynning'],
        117976 => ['S├Âlvesborg', 'Sölvesborg'],
        118209 => ['Sundsvallsfj├Ąrden', 'Sundsvallsfjärden'],
        153058 => ['Netherlands', 'CS1.1-NL-L1-Soil(P)'],
        153059 => ['Netherlands', 'CS1.1-NL-L2-Soil(P)'],
        153060 => ['Netherlands', 'CS1.1-NL-L3-Soil(P)'],
        153061 => ['Netherlands', 'CS1.1-NL-L4-Soil(P)'],
        153062 => ['Netherlands', 'CS1.1-NL-L5-Soil(P)'],
        153063 => ['Netherlands', 'CS1.1-NL-L6-Soil(P)'],
        153064 => ['Germany', 'CS1.2-DE-L1-Soil(P)'],
        153065 => ['Germany', 'CS1.2-DE-L2-Soil(P)'],
        153066 => ['Germany', 'CS1.2-DE-L3-Soil(P)'],
        153067 => ['Germany', 'CS1.2-DE-L4-Soil(P)'],
        153068 => ['Germany', 'CS1.2-DE-L5-Soil(P)'],
        153069 => ['Germany', 'CS1.2-DE-L6-Soil(P)'],
        153070 => ['Spain', 'CS1.3-ES-L1-Soil(P)'],
        153071 => ['Spain', 'CS1.3-ES-L2-Soil(P)'],
        153072 => ['Spain', 'CS1.3-ES-L3-Soil(P)'],
        153073 => ['Spain', 'CS1.3-ES-L4-Soil(P)'],
        153074 => ['Spain', 'CS1.3-ES-L5-Soil(P)'],
        153075 => ['Spain', 'CS1.3-ES-L6-Soil(P)'],
        153076 => ['Portugal', 'CS1.4-PT-L1-Soil(P)'],
        153077 => ['Portugal', 'CS1.4-PT-L2-Soil(P)'],
        153078 => ['Portugal', 'CS1.4-PT-L3-Soil(P)'],
        153079 => ['Portugal', 'CS1.4-PT-L4-Soil(P)'],
        153080 => ['Portugal', 'CS1.4-PT-L5-Soil(P)'],
        153081 => ['Portugal', 'CS1.4-PT-L6-Soil(P)'],
        153082 => ['Greece', 'CS1.5-EL-L1-Soil(P)'],
        153083 => ['Greece', 'CS1.5-EL-L2-Soil(P)'],
        153084 => ['Greece', 'CS1.5-EL-L3-Soil(P)'],
        153085 => ['Greece', 'CS1.5-EL-L4-Soil(P)'],
        153086 => ['Greece', 'CS1.5-EL-L5-Soil(P)'],
        153087 => ['Greece', 'CS1.5-EL-L6-Soil(P)'],
        153088 => ['Romania', 'CS1.6-RO-L1-Soil(P)'],
        153089 => ['Romania', 'CS1.6-RO-L2-Soil(P)'],
        153090 => ['Romania', 'CS1.6-RO-L3-Soil(P)'],
        153091 => ['Romania', 'CS1.6-RO-L4-Soil(P)'],
        153092 => ['Romania', 'CS1.6-RO-L5-Soil(P)'],
        153093 => ['Romania', 'CS1.6-RO-L6-Soil(P)'],
        153094 => ['Netherlands', 'CS2.1-NL-L1-Soil(P)'],
        153095 => ['Netherlands', 'CS2.1-NL-L2-Soil(P)'],
        153096 => ['Netherlands', 'CS2.1-NL-L3-Soil(P)'],
        153097 => ['Netherlands', 'CS2.1-NL-L4-Soil(P)'],
        153098 => ['Netherlands', 'CS2.1-NL-L5-Soil(P)'],
        153099 => ['Netherlands', 'CS2.1-NL-L6-Soil(P)'],
        153100 => ['Germany', 'CS2.2-DE-L1-Soil(P)'],
        153101 => ['Germany', 'CS2.2-DE-L2-Soil(P)'],
        153102 => ['Germany', 'CS2.2-DE-L3-Soil(P)'],
        153103 => ['Germany', 'CS2.2-DE-L4-Soil(P)'],
        153104 => ['Germany', 'CS2.2-DE-L5-Soil(P)'],
        153105 => ['Spain', 'CS2.3-ES-L1-Soil(P)'],
        153106 => ['Spain', 'CS2.3-ES-L2-Soil(P)'],
        153107 => ['Spain', 'CS2.3-ES-L3-Soil(P)'],
        153108 => ['Spain', 'CS2.3-ES-L4-Soil(P)'],
        153109 => ['Spain', 'CS2.3-ES-L5-Soil(P)'],
        153110 => ['Spain', 'CS2.3-ES-L6-Soil(P)'],
        153111 => ['Portugal', 'CS2.4-PT-L1-Soil(P)'],
        153112 => ['Portugal', 'CS2.4-PT-L2-Soil(P)'],
        153113 => ['Portugal', 'CS2.4-PT-L3-Soil(P)'],
        153114 => ['Portugal', 'CS2.4-PT-L4-Soil(P)'],
        153115 => ['Portugal', 'CS2.4-PT-L5-Soil(P)'],
        153116 => ['Portugal', 'CS2.4-PT-L6-Soil(P)'],
        153117 => ['Greece', 'CS2.5-EL-L1-Soil(P)'],
        153118 => ['Greece', 'CS2.5-EL-L2-Soil(P)'],
        153119 => ['Greece', 'CS2.5-EL-L3-Soil(P)'],
        153120 => ['Greece', 'CS2.5-EL-L4-Soil(P)'],
        153121 => ['Greece', 'CS2.5-EL-L5-Soil(P)'],
        153122 => ['Greece', 'CS2.5-EL-L6-Soil(P)'],
        153123 => ['Romania', 'CS2.6-RO-L1-Soil(P)'],
        153124 => ['Romania', 'CS2.6-RO-L2-Soil(P)'],
        153125 => ['Romania', 'CS2.6-RO-L3-Soil(P)'],
        153126 => ['Romania', 'CS2.6-RO-L4-Soil(P)'],
        153127 => ['Romania', 'CS2.6-RO-L5-Soil(P)'],
        153128 => ['Romania', 'CS2.6-RO-L6-Soil(P)'],
        153129 => ['Netherlands', 'CS1.1-NL-L3-Rod(a)(St-P)'],
        153130 => ['Netherlands', 'CS1.1-NL-L4-Rod(a)(St)'],
        153131 => ['Germany', 'CS1.2-DE-L1-Rod(a)(St-P)'],
        153132 => ['Germany', 'CS1.2-DE-L2-Rod(a)(St-P)'],
        153133 => ['Germany', 'CS1.2-DE-L3-Rod(a)(St-P)'],
        153134 => ['Germany', 'CS1.2-DE-L4-Rod(a)(St-P)'],
        153135 => ['Germany', 'CS1.2-DE-L5-Rod(a)(St)'],
        153136 => ['Germany', 'CS1.2-DE-L6-Rod(a)(St-P)'],
        153137 => ['Spain', 'CS1.3-ES-L2-Rod(a)(St-P)'],
        153138 => ['Spain', 'CS1.3-ES-L2-Rod(b)(St-P)'],
        153139 => ['Spain', 'CS1.3-ES-L3-Rod(a)(St-P)'],
        153140 => ['Spain', 'CS1.3-ES-L3-Rod(b)(St)'],
        153141 => ['Spain', 'CS1.3-ES-L4-Rod(a)(St-P)'],
        153142 => ['Spain', 'CS1.3-ES-L4-Rod(b)(St-P)'],
        153143 => ['Spain', 'CS1.3-ES-L5-Rod(a)(St-P)'],
        153144 => ['Spain', 'CS1.3-ES-L6-Rod(a)(St-P)'],
        153145 => ['Spain', 'CS1.3-ES-L6-Rod(b)(St-P)'],
        153146 => ['Portugal', 'CS1.4-PT-L1-Rod(a)(St-P)'],
        153147 => ['Portugal', 'CS1.4-PT-L2-Rod(a)(St-P)'],
        153148 => ['Portugal', 'CS1.4-PT-L3-Rod(a)(St-P)'],
        153149 => ['Portugal', 'CS1.4-PT-L4-Rod(a)(St-P)'],
        153150 => ['Portugal', 'CS1.4-PT-L4-Rod(b)(St)'],
        153151 => ['Portugal', 'CS1.4-PT-L5-Rod(a)(St-P)'],
        153152 => ['Portugal', 'CS1.4-PT-L5-Rod(b)(St-P)'],
        153153 => ['Portugal', 'CS1.4-PT-L6-Rod(a)(St-P)'],
        153154 => ['Greece', 'CS1.5-EL-L1-Rod(a)'],
        153155 => ['Greece', 'CS1.5-EL-L1-Rod(b)'],
        153156 => ['Greece', 'CS1.5-EL-L2-Rod(a)'],
        153157 => ['Greece', 'CS1.5-EL-L2-Rod(b)'],
        153158 => ['Greece', 'CS1.5-EL-L3-Rod(a)'],
        153159 => ['Greece', 'CS1.5-EL-L3-Rod(b)'],
        153160 => ['Greece', 'CS1.5-EL-L4-Rod(a)'],
        153161 => ['Greece', 'CS1.5-EL-L4-Rod(b)'],
        153162 => ['Greece', 'CS1.5-EL-L5-Rod(a)'],
        153163 => ['Greece', 'CS1.5-EL-L5-Rod(b)'],
        153164 => ['Greece', 'CS1.5-EL-L6-Rod(a)'],
        153165 => ['Greece', 'CS1.5-EL-L6-Rod(b)'],
        153166 => ['Romania', 'CS1.6-RO-L1-Rod(a)'],
        153167 => ['Romania', 'CS1.6-RO-L1-Rod(b)'],
        153168 => ['Romania', 'CS1.6-RO-L2-Rod(a)'],
        153169 => ['Romania', 'CS1.6-RO-L2-Rod(b)'],
        153170 => ['Romania', 'CS1.6-RO-L3-Rod(a)'],
        153171 => ['Romania', 'CS1.6-RO-L3-Rod(b)'],
        153172 => ['Romania', 'CS1.6-RO-L4-Rod(a)'],
        153173 => ['Romania', 'CS1.6-RO-L4-Rod(b)'],
        153174 => ['Romania', 'CS1.6-RO-L5-Rod(a)'],
        153175 => ['Romania', 'CS1.6-RO-L5-Rod(b)'],
        153176 => ['Romania', 'CS1.6-RO-L6-Rod(a)'],
        153177 => ['Romania', 'CS1.6-RO-L6-Rod(b)'],
        153178 => ['Netherlands', 'CS2.1-NL-L4-Rod(a)(St-P)'],
        153179 => ['Netherlands', 'CS2.1-NL-L4-Rod(b)(St-P)'],
        153180 => ['Germany', 'CS2.2-DE-L1-Ung(M-P)'],
        153181 => ['Germany', 'CS2.2-DE-L2-Ung(M-P)'],
        153182 => ['Germany', 'CS2.2-DE-L3-Ung(M)'],
        153183 => ['Germany', 'CS2.2-DE-L4-Ung(M-P)'],
        153184 => ['Germany', 'CS2.2-DE-L5-Ung(M-P)'],
        153185 => ['Spain', 'CS2.3-ES-L1-Rod(a)(St-P)'],
        153186 => ['Spain', 'CS2.3-ES-L6-Rod(a)(St-P)'],
        153187 => ['Spain', 'CS2.3-ES-L6-Rod(b)(St-P)'],
        153188 => ['Portugal', 'CS2.4-PT-L1-Rod(a)(St-P)'],
        153189 => ['Portugal', 'CS2.4-PT-L1-Rod(b)(St-P)'],
        153190 => ['Portugal', 'CS2.4-PT-L2-Rod(a)(St-P)'],
        153191 => ['Portugal', 'CS2.4-PT-L3-Rod(a)(St-P)'],
        153192 => ['Portugal', 'CS2.4-PT-L3-Rod(b)(St-P)'],
        153193 => ['Portugal', 'CS2.4-PT-L4-Rod(a)(St-P)'],
        153194 => ['Portugal', 'CS2.4-PT-L5-Rod(a)(St-P)'],
        153195 => ['Portugal', 'CS2.4-PT-L5-Rod(b)(St-P)'],
        153196 => ['Portugal', 'CS2.4-PT-L6-Rod(a)(St-P)'],
        153197 => ['Greece', 'CS2.5-EL-L1-Rod(a)'],
        153198 => ['Greece', 'CS2.5-EL-L1-Rod(b)'],
        153199 => ['Greece', 'CS2.5-EL-L2-Rod(a)'],
        153200 => ['Greece', 'CS2.5-EL-L2-Rod(b)'],
        153201 => ['Greece', 'CS2.5-EL-L3-Rod(a)'],
        153202 => ['Greece', 'CS2.5-EL-L3-Rod(b)'],
        153203 => ['Greece', 'CS2.5-EL-L4-Rod(a)'],
        153204 => ['Greece', 'CS2.5-EL-L4-Rod(b)'],
        153205 => ['Greece', 'CS2.5-EL-L5-Rod(a)'],
        153206 => ['Greece', 'CS2.5-EL-L5-Rod(b)'],
        153207 => ['Greece', 'CS2.5-EL-L6-Rod(a)'],
        153208 => ['Greece', 'CS2.5-EL-L6-Rod(b)'],
        153209 => ['Romania', 'CS2.6-RO-L1-Rod(a)(St-P)'],
        153210 => ['Romania', 'CS2.6-RO-L1-Rod(b)(St-P)'],
        153211 => ['Romania', 'CS2.6-RO-L2-Rod(a)(St-P)'],
        153212 => ['Romania', 'CS2.6-RO-L2-Rod(b)(St-P)'],
        153213 => ['Romania', 'CS2.6-RO-L3-Rod(a)(St-P)'],
        153214 => ['Romania', 'CS2.6-RO-L3-Rod(b)(St-P)'],
        153215 => ['Romania', 'CS2.6-RO-L4-Rod(a)(St-P)'],
        153216 => ['Romania', 'CS2.6-RO-L4-Rod(b)(St-P)'],
        153217 => ['Romania', 'CS2.6-RO-L5-Rod(a)(St-P)'],
        153218 => ['Romania', 'CS2.6-RO-L5-Rod(b)(St-P)'],
        153219 => ['Romania', 'CS2.6-RO-L6-Rod(a)(St-P)'],
        153220 => ['Romania', 'CS2.6-RO-L6-Rod(b)(St-P)'],
        153221 => ['Netherlands', 'CS1.1-NL-L1-Leaf(P)'],
        153222 => ['Netherlands', 'CS1.1-NL-L2-Leaf(P)'],
        153223 => ['Netherlands', 'CS1.1-NL-L3-Leaf(P)'],
        153224 => ['Netherlands', 'CS1.1-NL-L4-Leaf(P)'],
        153225 => ['Netherlands', 'CS1.1-NL-L5-Leaf(P)'],
        153226 => ['Netherlands', 'CS1.1-NL-L6-Leaf(P)'],
        153227 => ['Germany', 'CS1.2-DE-L1-Leaf(P)'],
        153228 => ['Germany', 'CS1.2-DE-L2-Leaf(P)'],
        153229 => ['Germany', 'CS1.2-DE-L3-Leaf(P)'],
        153230 => ['Germany', 'CS1.2-DE-L4-Leaf(P)'],
        153231 => ['Germany', 'CS1.2-DE-L5-Leaf(P)'],
        153232 => ['Germany', 'CS1.2-DE-L6-Leaf(P)'],
        153233 => ['Spain', 'CS1.3-ES-L1-Leaf(P)'],
        153234 => ['Spain', 'CS1.3-ES-L2-Leaf(P)'],
        153235 => ['Spain', 'CS1.3-ES-L3-Leaf(P)'],
        153236 => ['Spain', 'CS1.3-ES-L4-Leaf(P)'],
        153237 => ['Spain', 'CS1.3-ES-L5-Leaf(P)'],
        153238 => ['Spain', 'CS1.3-ES-L6-Leaf(P)'],
        153239 => ['Portugal', 'CS1.4-PT-L1-Leaf(P)'],
        153240 => ['Portugal', 'CS1.4-PT-L2-Leaf(P)'],
        153241 => ['Portugal', 'CS1.4-PT-L3-Leaf(P)'],
        153242 => ['Portugal', 'CS1.4-PT-L4-Leaf(P)'],
        153243 => ['Portugal', 'CS1.4-PT-L5-Leaf(P)'],
        153244 => ['Portugal', 'CS1.4-PT-L6-Leaf(P)'],
        153245 => ['Greece', 'CS1.5-EL-L1-Leaf(P)'],
        153246 => ['Greece', 'CS1.5-EL-L2-Leaf(P)'],
        153247 => ['Greece', 'CS1.5-EL-L3-Leaf(P)'],
        153248 => ['Greece', 'CS1.5-EL-L4-Leaf(P)'],
        153249 => ['Greece', 'CS1.5-EL-L5-Leaf(P)'],
        153250 => ['Greece', 'CS1.5-EL-L6-Leaf(P)'],
        153251 => ['Romania', 'CS1.6-RO-L1-Leaf(P)'],
        153252 => ['Romania', 'CS1.6-RO-L2-Leaf(P)'],
        153253 => ['Romania', 'CS1.6-RO-L3-Leaf(P)'],
        153254 => ['Romania', 'CS1.6-RO-L4-Leaf(P)'],
        153255 => ['Romania', 'CS1.6-RO-L5-Leaf(P)'],
        153256 => ['Romania', 'CS1.6-RO-L6-Leaf(P)'],
        153257 => ['Netherlands', 'CS2.1-NL-L1-Leaf(P)'],
        153258 => ['Netherlands', 'CS2.1-NL-L2-Leaf(P)'],
        153259 => ['Netherlands', 'CS2.1-NL-L3-Leaf(P)'],
        153260 => ['Netherlands', 'CS2.1-NL-L4-Leaf(P)'],
        153261 => ['Netherlands', 'CS2.1-NL-L5-Leaf(P)'],
        153262 => ['Netherlands', 'CS2.1-NL-L6-Leaf(P)'],
        153263 => ['Germany', 'CS2.2-DE-L1-Leaf(P)'],
        153264 => ['Germany', 'CS2.2-DE-L2-Leaf(P)'],
        153265 => ['Germany', 'CS2.2-DE-L3-Leaf(P)'],
        153266 => ['Germany', 'CS2.2-DE-L4-Leaf(P)'],
        153267 => ['Germany', 'CS2.2-DE-L5-Leaf(P)'],
        153268 => ['Spain', 'CS2.3-ES-L1-Leaf(P)'],
        153269 => ['Spain', 'CS2.3-ES-L2-Leaf(P)'],
        153270 => ['Spain', 'CS2.3-ES-L3-Leaf(P)'],
        153271 => ['Spain', 'CS2.3-ES-L4-Leaf(P)'],
        153272 => ['Spain', 'CS2.3-ES-L5-Leaf(P)'],
        153273 => ['Spain', 'CS2.3-ES-L6-Leaf(P)'],
        153274 => ['Portugal', 'CS2.4-PT-L1-Leaf(P)'],
        153275 => ['Portugal', 'CS2.4-PT-L2-Leaf(P)'],
        153276 => ['Portugal', 'CS2.4-PT-L3-Leaf(P)'],
        153277 => ['Portugal', 'CS2.4-PT-L4-Leaf(P)'],
        153278 => ['Portugal', 'CS2.4-PT-L5-Leaf(P)'],
        153279 => ['Portugal', 'CS2.4-PT-L6-Leaf(P)'],
        153280 => ['Greece', 'CS2.5-EL-L1-Leaf(P)'],
        153281 => ['Greece', 'CS2.5-EL-L2-Leaf(P)'],
        153282 => ['Greece', 'CS2.5-EL-L3-Leaf(P)'],
        153283 => ['Greece', 'CS2.5-EL-L4-Leaf(P)'],
        153284 => ['Greece', 'CS2.5-EL-L5-Leaf(P)'],
        153285 => ['Greece', 'CS2.5-EL-L6-Leaf(P)'],
        153286 => ['Romania', 'CS2.6-RO-L1-Leaf(P)'],
        153287 => ['Romania', 'CS2.6-RO-L2-Leaf(P)'],
        153288 => ['Romania', 'CS2.6-RO-L3-Leaf(P)'],
        153289 => ['Romania', 'CS2.6-RO-L4-Leaf(P)'],
        153290 => ['Romania', 'CS2.6-RO-L5-Leaf(P)'],
        153291 => ['Romania', 'CS2.6-RO-L6-Leaf(P)'],
        153292 => ['Netherlands', 'CS1.1-NL-L1-Inv(H-P)'],
        153293 => ['Netherlands', 'CS1.1-NL-L1-Inv(NH-P)'],
        153294 => ['Netherlands', 'CS1.1-NL-L2-Inv(H-P)'],
        153295 => ['Netherlands', 'CS1.1-NL-L2-Inv(NH-P)'],
        153296 => ['Netherlands', 'CS1.1-NL-L3-Inv(H-P)'],
        153297 => ['Netherlands', 'CS1.1-NL-L3-Inv(NH-P)'],
        153298 => ['Netherlands', 'CS1.1-NL-L4-Inv(H-P)'],
        153299 => ['Netherlands', 'CS1.1-NL-L4-Inv(NH-P)'],
        153300 => ['Netherlands', 'CS1.1-NL-L5-Inv(H-P)'],
        153301 => ['Netherlands', 'CS1.1-NL-L5-Inv(NH-P)'],
        153302 => ['Netherlands', 'CS1.1-NL-L6-Inv(H-P)'],
        153303 => ['Netherlands', 'CS1.1-NL-L6-Inv(NH-P)'],
        153304 => ['Germany', 'CS1.2-DE-L1-Inv(H-P)'],
        153305 => ['Germany', 'CS1.2-DE-L1-Inv(NH-P)'],
        153306 => ['Germany', 'CS1.2-DE-L2-Inv(H-P)'],
        153307 => ['Germany', 'CS1.2-DE-L2-Inv(NH-P)'],
        153308 => ['Germany', 'CS1.2-DE-L3-Inv(H-P)'],
        153309 => ['Germany', 'CS1.2-DE-L3-Inv(NH-P)'],
        153310 => ['Germany', 'CS1.2-DE-L4-Inv(H-P)'],
        153311 => ['Germany', 'CS1.2-DE-L4-Inv(NH-P)'],
        153312 => ['Germany', 'CS1.2-DE-L5-Inv(H-P)'],
        153313 => ['Germany', 'CS1.2-DE-L5-Inv(NH-P)'],
        153314 => ['Germany', 'CS1.2-DE-L6-Inv(H-P)'],
        153315 => ['Germany', 'CS1.2-DE-L6-Inv(NH-P)'],
        153316 => ['Spain', 'CS1.3-ES-L1-Inv(H-P)'],
        153317 => ['Spain', 'CS1.3-ES-L1-Inv(NH-P)'],
        153318 => ['Spain', 'CS1.3-ES-L2-Inv(H-P)'],
        153319 => ['Spain', 'CS1.3-ES-L2-Inv(NH-P)'],
        153320 => ['Spain', 'CS1.3-ES-L3-Inv(H-P)'],
        153321 => ['Spain', 'CS1.3-ES-L3-Inv(NH-P)'],
        153322 => ['Spain', 'CS1.3-ES-L4-Inv(H-P)'],
        153323 => ['Spain', 'CS1.3-ES-L4-Inv(NH-P)'],
        153324 => ['Spain', 'CS1.3-ES-L5-Inv(H-P)'],
        153325 => ['Spain', 'CS1.3-ES-L5-Inv(NH-P)'],
        153326 => ['Spain', 'CS1.3-ES-L6-Inv(H-P)'],
        153327 => ['Spain', 'CS1.3-ES-L6-Inv(NH-P)'],
        153328 => ['Portugal', 'CS1.4-PT-L1-Inv(H-P)'],
        153329 => ['Portugal', 'CS1.4-PT-L1-Inv(NH-P)'],
        153330 => ['Portugal', 'CS1.4-PT-L2-Inv(H-P)'],
        153331 => ['Portugal', 'CS1.4-PT-L2-Inv(NH-P)'],
        153332 => ['Portugal', 'CS1.4-PT-L3-Inv(H-P)'],
        153333 => ['Portugal', 'CS1.4-PT-L3-Inv(NH-P)'],
        153334 => ['Portugal', 'CS1.4-PT-L4-Inv(H-P)'],
        153335 => ['Portugal', 'CS1.4-PT-L4-Inv(NH-P)'],
        153336 => ['Portugal', 'CS1.4-PT-L5-Inv(H-P)'],
        153337 => ['Portugal', 'CS1.4-PT-L5-Inv(NH-P)'],
        153338 => ['Portugal', 'CS1.4-PT-L6-Inv(H-P)'],
        153339 => ['Portugal', 'CS1.4-PT-L6-Inv(NH-P)'],
        153340 => ['Greece', 'CS1.5-EL-L1-Inv(H-P)'],
        153341 => ['Greece', 'CS1.5-EL-L1-Inv(NH-P)'],
        153342 => ['Greece', 'CS1.5-EL-L2-Inv(H-P)'],
        153343 => ['Greece', 'CS1.5-EL-L2-Inv(NH-P)'],
        153344 => ['Greece', 'CS1.5-EL-L3-Inv(H-P)'],
        153345 => ['Greece', 'CS1.5-EL-L3-Inv(NH-P)'],
        153346 => ['Greece', 'CS1.5-EL-L4-Inv(H-P)'],
        153347 => ['Greece', 'CS1.5-EL-L4-Inv(NH-P)'],
        153348 => ['Greece', 'CS1.5-EL-L5-Inv(H-P)'],
        153349 => ['Greece', 'CS1.5-EL-L5-Inv(NH-P)'],
        153350 => ['Greece', 'CS1.5-EL-L6-Inv(H-P)'],
        153351 => ['Greece', 'CS1.5-EL-L6-Inv(NH-P)'],
        153352 => ['Romania', 'CS1.6-RO-L1-Inv(H-P)'],
        153353 => ['Romania', 'CS1.6-RO-L1-Inv(NH-P)'],
        153354 => ['Romania', 'CS1.6-RO-L2-Inv(H-P)'],
        153355 => ['Romania', 'CS1.6-RO-L2-Inv(NH-P)'],
        153356 => ['Romania', 'CS1.6-RO-L3-Inv(H-P)'],
        153357 => ['Romania', 'CS1.6-RO-L3-Inv(NH-P)'],
        153358 => ['Romania', 'CS1.6-RO-L4-Inv(H-P)'],
        153359 => ['Romania', 'CS1.6-RO-L4-Inv(NH-P)'],
        153360 => ['Romania', 'CS1.6-RO-L5-Inv(H-P)'],
        153361 => ['Romania', 'CS1.6-RO-L5-Inv(NH-P)'],
        153362 => ['Romania', 'CS1.6-RO-L6-Inv(H-P)'],
        153363 => ['Romania', 'CS1.6-RO-L6-Inv(NH-P)'],
        153364 => ['Netherlands', 'CS2.1-NL-L1-Inv(H-P)'],
        153365 => ['Netherlands', 'CS2.1-NL-L1-Inv(NH-P)'],
        153366 => ['Netherlands', 'CS2.1-NL-L2-Inv(H-P)'],
        153367 => ['Netherlands', 'CS2.1-NL-L2-Inv(NH-P)'],
        153368 => ['Netherlands', 'CS2.1-NL-L3-Inv(H-P)'],
        153369 => ['Netherlands', 'CS2.1-NL-L3-Inv(NH-P)'],
        153370 => ['Netherlands', 'CS2.1-NL-L4-Inv(H-P)'],
        153371 => ['Netherlands', 'CS2.1-NL-L4-Inv(NH-P)'],
        153372 => ['Netherlands', 'CS2.1-NL-L5-Inv(H-P)'],
        153373 => ['Netherlands', 'CS2.1-NL-L5-Inv(NH-P)'],
        153374 => ['Netherlands', 'CS2.1-NL-L6-Inv(H-P)'],
        153375 => ['Netherlands', 'CS2.1-NL-L6-Inv(NH-P)'],
        153376 => ['Germany', 'CS2.2-DE-L1-Inv(H-P)'],
        153377 => ['Germany', 'CS2.2-DE-L1-Inv(NH-P)'],
        153378 => ['Germany', 'CS2.2-DE-L2-Inv(H-P)'],
        153379 => ['Germany', 'CS2.2-DE-L2-Inv(NH-P)'],
        153380 => ['Germany', 'CS2.2-DE-L3-Inv(H-P)'],
        153381 => ['Germany', 'CS2.2-DE-L3-Inv(NH-P)'],
        153382 => ['Germany', 'CS2.2-DE-L4-Inv(H-P)'],
        153383 => ['Germany', 'CS2.2-DE-L4-Inv(NH-P)'],
        153384 => ['Germany', 'CS2.2-DE-L5-Inv(H-P)'],
        153385 => ['Germany', 'CS2.2-DE-L5-Inv(NH-P)'],
        153386 => ['Spain', 'CS2.3-ES-L1-Inv(H-P)'],
        153387 => ['Spain', 'CS2.3-ES-L1-Inv(NH-P)'],
        153388 => ['Spain', 'CS2.3-ES-L2-Inv(H-P)'],
        153389 => ['Spain', 'CS2.3-ES-L2-Inv(NH-P)'],
        153390 => ['Spain', 'CS2.3-ES-L3-Inv(H-P)'],
        153391 => ['Spain', 'CS2.3-ES-L3-Inv(NH-P)'],
        153392 => ['Spain', 'CS2.3-ES-L4-Inv(H-P)'],
        153393 => ['Spain', 'CS2.3-ES-L4-Inv(NH-P)'],
        153394 => ['Spain', 'CS2.3-ES-L5-Inv(H-P)'],
        153395 => ['Spain', 'CS2.3-ES-L5-Inv(NH-P)'],
        153396 => ['Spain', 'CS2.3-ES-L6-Inv(H-P)'],
        153397 => ['Spain', 'CS2.3-ES-L6-Inv(NH-P)'],
        153398 => ['Portugal', 'CS2.4-PT-L1-Inv(H-P)'],
        153399 => ['Portugal', 'CS2.4-PT-L1-Inv(NH-P)'],
        153400 => ['Portugal', 'CS2.4-PT-L2-Inv(H-P)'],
        153401 => ['Portugal', 'CS2.4-PT-L2-Inv(NH-P)'],
        153402 => ['Portugal', 'CS2.4-PT-L3-Inv(H-P)'],
        153403 => ['Portugal', 'CS2.4-PT-L3-Inv(NH-P)'],
        153404 => ['Portugal', 'CS2.4-PT-L4-Inv(H-P)'],
        153405 => ['Portugal', 'CS2.4-PT-L4-Inv(NH-P)'],
        153406 => ['Portugal', 'CS2.4-PT-L5-Inv(H-P)'],
        153407 => ['Portugal', 'CS2.4-PT-L5-Inv(NH-P)'],
        153408 => ['Portugal', 'CS2.4-PT-L6-Inv(H-P)'],
        153409 => ['Portugal', 'CS2.4-PT-L6-Inv(NH-P)'],
        153410 => ['Greece', 'CS2.5-EL-L1-Inv(H-P)'],
        153411 => ['Greece', 'CS2.5-EL-L1-Inv(NH-P)'],
        153412 => ['Greece', 'CS2.5-EL-L2-Inv(H-P)'],
        153413 => ['Greece', 'CS2.5-EL-L2-Inv(NH-P)'],
        153414 => ['Greece', 'CS2.5-EL-L3-Inv(H-P)'],
        153415 => ['Greece', 'CS2.5-EL-L3-Inv(NH-P)'],
        153416 => ['Greece', 'CS2.5-EL-L4-Inv(H-P)'],
        153417 => ['Greece', 'CS2.5-EL-L4-Inv(NH-P)'],
        153418 => ['Greece', 'CS2.5-EL-L5-Inv(H-P)'],
        153419 => ['Greece', 'CS2.5-EL-L5-Inv(NH-P)'],
        153420 => ['Greece', 'CS2.5-EL-L6-Inv(H-P)'],
        153421 => ['Greece', 'CS2.5-EL-L6-Inv(NH-P)'],
        153422 => ['Romania', 'CS2.6-RO-L1-Inv(H-P)'],
        153423 => ['Romania', 'CS2.6-RO-L2-Inv(H-P)'],
        153424 => ['Romania', 'CS2.6-RO-L2-Inv(NH-P)'],
        153425 => ['Romania', 'CS2.6-RO-L3-Inv(H-P)'],
        153426 => ['Romania', 'CS2.6-RO-L3-Inv(NH-P)'],
        153427 => ['Romania', 'CS2.6-RO-L4-Inv(H-P)'],
        153428 => ['Romania', 'CS2.6-RO-L4-Inv(NH-P)'],
        153429 => ['Romania', 'CS2.6-RO-L5-Inv(H-P)'],
        153430 => ['Romania', 'CS2.6-RO-L5-Inv(NH-P)'],
        153431 => ['Romania', 'CS2.6-RO-L6-Inv(H-P)'],
        153432 => ['Romania', 'CS2.6-RO-L6-Inv(NH-P)'],
        153433 => ['Netherlands', 'CS1.1-NL-L1-Ta(Lv)'],
        153434 => ['Netherlands', 'CS1.1-NL-L2-Ta(Lv)'],
        153435 => ['Netherlands', 'CS1.1-NL-L3-Ta(Lv)'],
        153436 => ['Netherlands', 'CS1.1-NL-L4-Ta(Lv)'],
        153437 => ['Netherlands', 'CS1.1-NL-L5-Ta(Lv)'],
        153438 => ['Netherlands', 'CS1.1-NL-L6-Ta(Lv)'],
        153439 => ['Germany', 'CS1.2-DE-L1-Ta(Lv)'],
        153440 => ['Germany', 'CS1.2-DE-L2-Ta(Lv)'],
        153441 => ['Germany', 'CS1.2-DE-L3-Ta(Lv)'],
        153442 => ['Germany', 'CS1.2-DE-L4-Ta(Lv)'],
        153443 => ['Germany', 'CS1.2-DE-L5-Ta(Lv)'],
        153444 => ['Germany', 'CS1.2-DE-L6-Ta(Lv)'],
        153445 => ['Spain', 'CS1.3-ES-L1-Ta(Lv)'],
        153446 => ['Spain', 'CS1.3-ES-L2-Ta(Lv)'],
        153447 => ['Spain', 'CS1.3-ES-L3-Ta(Lv)'],
        153448 => ['Spain', 'CS1.3-ES-L4-Ta(Lv)'],
        153449 => ['Spain', 'CS1.3-ES-L5-Ta(Lv)'],
        153450 => ['Spain', 'CS1.3-ES-L6-Ta(Lv)'],
        153451 => ['Portugal', 'CS1.4-PT-L1-Ta(Lv)'],
        153452 => ['Portugal', 'CS1.4-PT-L2-Ta(Lv)'],
        153453 => ['Portugal', 'CS1.4-PT-L3-Ta(Lv)'],
        153454 => ['Portugal', 'CS1.4-PT-L4-Ta(Lv)'],
        153455 => ['Portugal', 'CS1.4-PT-L5-Ta(Lv)'],
        153456 => ['Portugal', 'CS1.4-PT-L6-Ta(Lv)'],
        153457 => ['Greece', 'CS1.5-EL-L1-Ta(Lv)'],
        153458 => ['Greece', 'CS1.5-EL-L2-Ta(Lv)'],
        153459 => ['Greece', 'CS1.5-EL-L3-Ta(Lv)'],
        153460 => ['Greece', 'CS1.5-EL-L4-Ta(Lv)'],
        153461 => ['Greece', 'CS1.5-EL-L5-Ta(Lv)'],
        153462 => ['Greece', 'CS1.5-EL-L6-Ta(Lv)'],
        153463 => ['Romania', 'CS1.6-RO-L1-Ta(Lv)'],
        153464 => ['Romania', 'CS1.6-RO-L2-Ta(Lv)'],
        153465 => ['Romania', 'CS1.6-RO-L3-Ta(Lv)'],
        153466 => ['Romania', 'CS1.6-RO-L4-Ta(Lv)'],
        153467 => ['Romania', 'CS1.6-RO-L5-Ta(Lv)'],
        153468 => ['Romania', 'CS1.6-RO-L6-Ta(Lv)'],
        153469 => ['Netherlands', 'CS2.1-NL-L1-Mf(Lv)'],
        153470 => ['Netherlands', 'CS2.1-NL-L2-Mf(Lv)'],
        153471 => ['Netherlands', 'CS2.1-NL-L3-Mf(Lv)'],
        153472 => ['Netherlands', 'CS2.1-NL-L4-Mf(Lv)'],
        153473 => ['Netherlands', 'CS2.1-NL-L5-Mf(Lv)'],
        153474 => ['Netherlands', 'CS2.1-NL-L6-Mf(Lv)'],
        153475 => ['Germany', 'CS2.2-DE-L1-Cl(Lv)'],
        153476 => ['Germany', 'CS2.2-DE-L2-Cl(Lv)'],
        153477 => ['Germany', 'CS2.2-DE-L3-Cl(Lv)'],
        153478 => ['Germany', 'CS2.2-DE-L4-Cl(Lv)'],
        153479 => ['Germany', 'CS2.2-DE-L5-Cl(Lv)'],
        153480 => ['Spain', 'CS2.3-ES-L1-Mm(Lv)'],
        153481 => ['Spain', 'CS2.3-ES-L2-Mm(Lv)'],
        153482 => ['Spain', 'CS2.3-ES-L3-Mm(Lv)'],
        153483 => ['Spain', 'CS2.3-ES-L4-Mm(Lv)'],
        153484 => ['Spain', 'CS2.3-ES-L5-Mm(Lv)'],
        153485 => ['Spain', 'CS2.3-ES-L6-Mm(Lv)'],
        153486 => ['Portugal', 'CS2.4-PT-L1-Hi(Lv)'],
        153487 => ['Portugal', 'CS2.4-PT-L2-Hi(Lv)'],
        153488 => ['Portugal', 'CS2.4-PT-L3-Hi(Lv)'],
        153489 => ['Portugal', 'CS2.4-PT-L4-Hi(Lv)'],
        153490 => ['Portugal', 'CS2.4-PT-L5-Hi(Lv)'],
        153491 => ['Portugal', 'CS2.4-PT-L6-Hi(Lv)'],
        153492 => ['Greece', 'CS2.5-EL-L1-Er(Lv)'],
        153493 => ['Greece', 'CS2.5-EL-L2-Er(Lv)'],
        153494 => ['Greece', 'CS2.5-EL-L3-Er(Lv)'],
        153495 => ['Greece', 'CS2.5-EL-L4-Er(Lv)'],
        153496 => ['Greece', 'CS2.5-EL-L5-Er(Lv)'],
        153497 => ['Greece', 'CS2.5-EL-L6-Er(Lv)'],
        153498 => ['Romania', 'CS2.6-RO-L1-Vv(Lv)'],
        153499 => ['Romania', 'CS2.6-RO-L2-Vv(Lv)'],
        153500 => ['Romania', 'CS2.6-RO-L3-Vv(Lv)'],
        153501 => ['Romania', 'CS2.6-RO-L4-Vv(Lv)'],
        153502 => ['Romania', 'CS2.6-RO-L5-Vv(Lv)'],
        153503 => ['Romania', 'CS2.6-RO-L6-Vv(Lv)'],
    ];

    /** @var list<int> */
    private array $toChange = [];

    protected function plan(): int
    {
        $this->toChange = [];
        $done = 0;
        $problems = [];

        $current = $this->pg->table(self::TABLE)
            ->whereIn('id', array_keys(self::STATIONS))
            ->get(['id', 'name'])
            ->keyBy('id');

        foreach (self::STATIONS as $id => $names) {
            $state = $this->state($current->get($id), $names);
            if ($state === 'change') {
                $this->toChange[] = $id;
            } elseif ($state === 'done') {
                $done++;
            } else {
                $problems[] = $id;
            }
        }

        $this->say('Listed stations: '.count(self::STATIONS));
        $this->say('Names to correct: '.count($this->toChange));
        $this->say('Already done: '.$done);
        $this->say('Missing or other name: '.count($problems));
        if ($problems !== []) {
            $this->say('Problem ids: '.implode(', ', $problems));
        }

        return count($this->toChange);
    }

    protected function apply(ReconcileAudit $audit): void
    {
        $started = microtime(true);

        $this->pg->transaction(function () use ($audit, $started): void {
            $current = $this->pg->table(self::TABLE)
                ->whereIn('id', array_keys(self::STATIONS))
                ->lockForUpdate()
                ->get(['id', 'name'])
                ->keyBy('id');

            $ids = [];
            $before = [];
            $problems = [];
            foreach (self::STATIONS as $id => $names) {
                $row = $current->get($id);
                $state = $this->state($row, $names);
                if ($state === 'change') {
                    $ids[] = $id;
                    $before[] = [
                        'id' => $id,
                        'name' => $row->name,
                    ];
                } elseif ($state === 'other') {
                    $problems[] = $id;
                }
            }

            if ($problems !== []) {
                throw new RuntimeException('Stations missing or with another name than listed, nothing written: '.implode(', ', $problems));
            }

            foreach ($ids as $id) {
                $this->pg->table(self::TABLE)->where('id', $id)->update([
                    'name' => self::STATIONS[$id][1],
                ]);
            }

            $audit->logFill(
                'Station names',
                self::TABLE,
                ['name'],
                $ids,
                $before,
                (int) ((microtime(true) - $started) * 1000),
            );
            $this->say('Rows changed: '.count($ids).' of '.count($this->toChange).' planned.');
        });
    }

    protected function notes(): string
    {
        return 'Correct the name of stations in empodat_stations to the legacy name';
    }

    /**
     * @param  array{0: string, 1: string}  $names
     * @return 'change'|'done'|'other'
     */
    private function state(?stdClass $row, array $names): string
    {
        if ($row === null) {
            return 'other';
        }

        if ($row->name === $names[0]) {
            return 'change';
        }

        return $row->name === $names[1] ? 'done' : 'other';
    }
}
