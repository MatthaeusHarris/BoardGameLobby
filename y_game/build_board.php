<pre><?php
$board = array(
  array(448,	100),
  array(503,	126),
  array(558,	162),
  array(608,	207),
  array(649,	263),
  array(679,	325),
  array(695,	390),
  array(700,	453),
  array(697,	512),
  array(646,	545),
  array(587,	573),
  array(521,	593),
  array(451,	600),
  array(381,	594),
  array(315,	575),
  array(255,	547),
  array(204,	515),
  array(200,	456),
  array(204,	392),
  array(220,	327),
  array(248,	265),
  array(289,	209),
  array(339,	163),
  array(393,	127),
  array(448,	148),
  array(503,	174),
  array(554,	211),
  array(597,	257),
  array(629,	310),
  array(649,	369),
  array(657,	429),
  array(654,	489),
  array(603,	521),
  array(545,	546),
  array(482,	559),
  array(419,	559),
  array(356,	547),
  array(298,	523),
  array(246,	491),
  array(243,	432),
  array(250,	371),
  array(269,	312),
  array(301,	258),
  array(343,	212),
  array(394,	175),
  array(448,	192),
  array(501,	220),
  array(545,	257),
  array(578,	303),
  array(603,	353),
  array(615,	409),
  array(614,	467),
  array(563,	497),
  array(507,	515),
  array(450,	520),
  array(393,	516),
  array(337,	498),
  array(286,	468),
  array(285,	411),
  array(296,	355),
  array(320,	304),
  array(352,	259),
  array(397,	220),
  array(449,	234),
  array(495,	264),
  array(527,	303),
  array(553,	346),
  array(572,	392),
  array(576,	446),
  array(526,	470),
  array(475,	477),
  array(425,	477),
  array(374,	471),
  array(324,	447),
  array(327,	393),
  array(346,	347),
  array(371,	304),
  array(402,	265),
  array(449,	275),
  array(476,	309),
  array(501,	346),
  array(522,	386),
  array(540,	425),
  array(496,	431),
  array(450,	434),
  array(404,	432),
  array(360,	426),
  array(377,	386),
  array(397,	347),
  array(422,	309),
  array(449,	348),
  array(474,	389),
  array(425,	389),
);


foreach ($board as $key => $position) {
  echo "<mx:Canvas data=\"$key\" x=\"{$position[0]}\" y=\"{$position[1]}\" id=\"position_{$key}\" width=\"20\" height=\"20\" dragEnter=\"dragEnterHandler(event)\" dragDrop=\"dropHandler(event)\" backgroundColor=\"#ffffff\" backgroundAlpha=\"0\"/>\n";
}

?></pre>