<?php

namespace Database\Seeders\Cefr;

use App\Enums\LanguageLevel;

class A2PathSeeder extends CefrPathSeeder
{
    protected function level(): LanguageLevel
    {
        return LanguageLevel::A2;
    }

    protected function pathName(): string
    {
        return 'Everyday Bulgarian';
    }

    protected function lessons(): array
    {
        return [
            [
                'name' => 'Catching Up',
                'description' => 'Meet an old friend again and say where you were with the past tense of "съм".',
                'exercises' => [
                    self::pairs('Match the catching-up phrases', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['Long time no see', 'Отдавна не сме се виждали'],
                        ["What's new?", 'Какво ново?'],
                        ['Nothing special', 'Нищо особено'],
                        ['Take care', 'Пази се'],
                        ['Say hi to your mum', 'Поздрави майка си'],
                    ]),
                    self::fill('I was at home', 'Вчера аз __ вкъщи цял ден.', ['беше', 'бях', 'бяха'], 1,
                        '"Вчера аз бях вкъщи цял ден." means "Yesterday I was at home all day." Бях is the "I" form of the past of съм.'),
                    self::trueFalse('True or false: Byahme', '"Бяхме" is the past tense of "съм" for "ние" (we were).', true,
                        'Correct. The past of съм runs бях, беше, беше, бяхме, бяхте, бяха.'),
                    self::fill('Where were you, Ivan', 'Къде __ миналата седмица, Иване?', ['бях', 'бяхте', 'беше'], 2,
                        '"Къде беше миналата седмица, Иване?" means "Where were you last week, Ivan?" Иване is a friendly form of address, so the informal "ти" form беше fits.'),
                    self::trueFalse('True or false: Pazi se', '"Пази се!" is something you say when you first meet someone.', false,
                        '"Пази се!" means "Take care!" and is said when you part.'),
                    self::pairs('Match the past of to be', 'Match each English phrase to its Bulgarian past form of "съм".', [
                        ['I was', 'бях'],
                        ['He was', 'беше'],
                        ['We were', 'бяхме'],
                        ['You were (a group)', 'бяхте'],
                        ['They were', 'бяха'],
                    ]),
                    self::fill('Last summer we were', 'Миналото лято ние __ на море в Созопол.', ['бяха', 'бяхме', 'бях'], 1,
                        '"Миналото лято ние бяхме на море в Созопол." means "Last summer we were at the seaside in Sozopol."'),
                ],
            ],
            [
                'name' => 'Tell Me About Yourself',
                'description' => 'Talk about likes and belongings with the short possessive forms ми, ти, му, ѝ.',
                'exercises' => [
                    self::pairs('Match the possessives', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['My name', 'името ми'],
                        ['Your job', 'работата ти'],
                        ['His car', 'колата му'],
                        ['Her brother', 'брат ѝ'],
                        ['Our house', 'къщата ни'],
                    ]),
                    self::fill('Your sister', 'Как се казва __ ти?', ['сестрата', 'сестра', 'сестри'], 1,
                        '"Как се казва сестра ти?" means "What is your sister\'s name?" Close family words in the singular take no article before ми, ти, му, ѝ.'),
                    self::trueFalse('True or false: Haresva mi', '"Харесва ми" means "I like it", literally "It pleases me".', true,
                        'Correct. The thing you like is the subject, and "ми" (to me) shows who likes it.'),
                    self::fill('I like reading', 'На мен ми __ да чета книги.', ['харесвам', 'харесват', 'харесва'], 2,
                        '"На мен ми харесва да чета книги." means "I like reading books." The verb agrees with the activity you like, not with you.'),
                    self::trueFalse('True or false: Maikata mi', 'With close family words in the singular you add the article before the short possessive: "майката ми".', false,
                        'It is "майка ми", "баща ми", "брат ми" with no article. Most other nouns do take it: "колата ми".'),
                    self::pairs('Match the hobbies', 'Match each hobby to its Bulgarian word.', [
                        ['Reading', 'четене'],
                        ['Cooking', 'готвене'],
                        ['Hiking', 'планинарство'],
                        ['Swimming', 'плуване'],
                        ['Photography', 'фотография'],
                    ]),
                    self::fill('I like to go to the mountains', 'Обичам да __ в планината през уикенда.', ['ходиш', 'ходят', 'ходя'], 2,
                        '"Обичам да ходя в планината през уикенда." means "I like going to the mountains at the weekend." After "обичам да" the verb stays in the "I" form.'),
                ],
            ],
            [
                'name' => 'Family Stories',
                'description' => 'Weddings, birthdays and relatives, told in the simple past tense.',
                'exercises' => [
                    self::pairs('Match the family events', 'Match each English word to its Bulgarian equivalent.', [
                        ['Wedding', 'сватба'],
                        ['Birthday', 'рожден ден'],
                        ['Cousin (male)', 'братовчед'],
                        ['Aunt', 'леля'],
                        ['Grandson', 'внук'],
                    ]),
                    self::fill('My sister got married', 'Сестра ми __ миналата година.', ['се ожени', 'се омъжи', 'се омъжих'], 1,
                        '"Сестра ми се омъжи миналата година." means "My sister got married last year." A woman "се омъжва", a man "се жени".'),
                    self::trueFalse('True or false: Vuycho and chicho', '"Вуйчо" is your mother\'s brother, while "чичо" is your father\'s brother.', true,
                        'Correct. Bulgarian keeps the two uncles apart: вуйчо on the mother\'s side, чичо on the father\'s.'),
                    self::fill('We called grandma', 'Вчера ние __ на баба за рождения ѝ ден.', ['се обадихме', 'се обади', 'се обадиха'], 0,
                        '"Вчера ние се обадихме на баба за рождения ѝ ден." means "Yesterday we called grandma for her birthday."'),
                    self::trueFalse('True or false: Rodih se', '"Родих се през 1990 г." means "I got married in 1990."', false,
                        '"Родих се през 1990 г." means "I was born in 1990."'),
                    self::pairs('Match the past forms', 'Match each English past form to its Bulgarian equivalent.', [
                        ['I went', 'отидох'],
                        ['I saw', 'видях'],
                        ['I bought', 'купих'],
                        ['I ate', 'ядох'],
                        ['I came', 'дойдох'],
                    ]),
                    self::fill('We danced until morning', 'Миналата събота на сватбата __ до сутринта.', ['ще танцуваме', 'танцувахме', 'танцуваме'], 1,
                        '"Миналата събота на сватбата танцувахме до сутринта." means "Last Saturday at the wedding we danced until morning."'),
                ],
            ],
            [
                'name' => 'Plans for the Week',
                'description' => 'Numbers up to a hundred, appointments and the future tense with "ще".',
                'exercises' => [
                    self::pairs('Match the numbers', 'Match each number to its Bulgarian word.', [
                        ['30', 'тридесет'],
                        ['45', 'четиридесет и пет'],
                        ['50', 'петдесет'],
                        ['70', 'седемдесет'],
                        ['100', 'сто'],
                    ]),
                    self::fill('Tomorrow I will go', 'Утре __ отида на фитнес.', ['щях', 'ще', 'да'], 1,
                        '"Утре ще отида на фитнес." means "Tomorrow I will go to the gym." Ще comes before the verb to make the future.'),
                    self::trueFalse('True or false: Nyama da', 'The negative future is made with "няма да": "Няма да дойда."', true,
                        'Correct. "Няма да дойда" means "I will not come". Bulgarian does not say "не ще".'),
                    self::fill('Half past eight', 'Филмът започва в осем и __ вечерта.', ['половин', 'половината', 'половина'], 2,
                        '"Филмът започва в осем и половина вечерта." means "The film starts at half past eight in the evening." Половин is used before a noun, as in половин час.'),
                    self::trueFalse('True or false: Shte changes', 'In the future tense "ще" changes with the person: "аз щя, ти щеш".', false,
                        'Ще never changes in the future: аз ще, ти ще, те ще. Only the main verb changes.'),
                    self::pairs('Match the future times', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['Next week', 'следващата седмица'],
                        ['The day after tomorrow', 'вдругиден'],
                        ['Tonight', 'довечера'],
                        ['Soon', 'скоро'],
                        ['In a month', 'след месец'],
                    ]),
                    self::fill('Tonight we will watch', 'Довечера __ гледаме филм у нас.', ['щях', 'ще', 'няма'], 1,
                        '"Довечера ще гледаме филм у нас." means "Tonight we will watch a film at our place." "Няма" would need "да": "няма да гледаме".'),
                ],
            ],
            [
                'name' => 'Finding a Flat',
                'description' => 'Rent a flat and refer back to things with го, я and ги.',
                'exercises' => [
                    self::pairs('Match the renting words', 'Match each English word to its Bulgarian equivalent.', [
                        ['Rent', 'наем'],
                        ['Landlord', 'хазяин'],
                        ['Furniture', 'мебели'],
                        ['Floor (storey)', 'етаж'],
                        ['Balcony', 'балкон'],
                    ]),
                    self::fill('I will take it', 'Харесвам апартамента и ще __ взема.', ['го', 'я', 'ги'], 0,
                        '"Харесвам апартамента и ще го взема." means "I like the flat and I will take it." Апартамент is masculine, so "it" is го.'),
                    self::trueFalse('True or false: Vidyah ya', 'In "Видях я вчера", "я" means "her", or a feminine thing.', true,
                        'Correct. The sentence means "I saw her yesterday". Я replaces a feminine noun.'),
                    self::fill('I do not like them', 'Столовете са нови, но не __ харесвам.', ['го', 'ги', 'я'], 1,
                        '"Столовете са нови, но не ги харесвам." means "The chairs are new, but I do not like them." Ги replaces a plural noun.'),
                    self::trueFalse('True or false: Tretiya etazh', '"Апартаментът е на третия етаж." means "The flat is on the thirteenth floor."', false,
                        'Третия means "third". Thirteenth is "тринадесетия".'),
                    self::pairs('Match the flat adjectives', 'Match each English adjective to its Bulgarian equivalent.', [
                        ['Spacious', 'просторен'],
                        ['Bright', 'светъл'],
                        ['Quiet', 'тих'],
                        ['Cheap', 'евтин'],
                        ['Expensive', 'скъп'],
                    ]),
                    self::fill('A bright kitchen', 'Кухнята е малка, но много __ през деня.', ['светъл', 'светли', 'светла'], 2,
                        '"Кухнята е малка, но много светла през деня." means "The kitchen is small but very bright during the day." Кухня is feminine.'),
                ],
            ],
            [
                'name' => 'Dinner Guests',
                'description' => 'Toasts and table talk, with the short forms ми, ти, му, ѝ for "to me", "to you", "to him" and "to her".',
                'exercises' => [
                    self::pairs('Match the table talk', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['Cheers!', 'Наздраве!'],
                        ['Enjoy your meal', 'Добър апетит'],
                        ['Help yourself', 'Заповядай'],
                        ["It's delicious", 'Много е вкусно'],
                        ['Pass me the salt, please', 'Подай ми солта, моля'],
                    ]),
                    self::fill('Pass me the bread', 'Може ли да __ дадеш хляба?', ['ме', 'мен', 'ми'], 2,
                        '"Може ли да ми дадеш хляба?" means "Could you give me the bread?" Ми means "to me".'),
                    self::trueFalse('True or false: Nazdrave', 'When Bulgarians clink glasses they say "Наздраве!"', true,
                        'Correct. "Наздраве!" means "To your health!" and is the usual toast.'),
                    self::fill('Flowers for her', 'Донесох __ цветя, защото е рожденият ѝ ден.', ['му', 'ѝ', 'им'], 1,
                        '"Донесох ѝ цветя, защото е рожденият ѝ ден." means "I brought her flowers because it is her birthday." Ѝ means "to her".'),
                    self::trueFalse('True or false: Sit sam', '"Сит съм" means "I am hungry".', false,
                        '"Сит съм" means "I am full". "I am hungry" is "Гладен съм".'),
                    self::pairs('Match the courses', 'Match each dish to its Bulgarian word.', [
                        ['Soup', 'супа'],
                        ['Salad', 'салата'],
                        ['Meat', 'месо'],
                        ['Fish', 'риба'],
                        ['Dessert', 'десерт'],
                    ]),
                    self::fill('Lentil soup', 'За първо ядене има __ от леща.', ['десерт', 'супа', 'торта'], 1,
                        '"За първо ядене има супа от леща." means "For the first course there is lentil soup."'),
                ],
            ],
            [
                'name' => 'A Day at the Office',
                'description' => 'Emails, meetings and deadlines, with more verbs in the past tense.',
                'exercises' => [
                    self::pairs('Match the office words', 'Match each English word to its Bulgarian equivalent.', [
                        ['Meeting', 'среща'],
                        ['Deadline', 'краен срок'],
                        ['Colleague', 'колега'],
                        ['Salary', 'заплата'],
                        ['Leave (time off)', 'отпуск'],
                    ]),
                    self::fill('I wrote an email', 'Вчера __ имейл на шефа си.', ['написах', 'пиша', 'ще пиша'], 0,
                        '"Вчера написах имейл на шефа си." means "Yesterday I wrote an email to my boss." Вчера (yesterday) calls for the past tense.'),
                    self::trueFalse('True or false: Shefkata', '"Шефката" refers to a female boss.', true,
                        'Correct. Шеф is the boss, шефка a female boss, and -та is the article.'),
                    self::fill('The meeting started', 'Срещата __ в десет, но аз закъснях.', ['започва', 'ще започне', 'започна'], 2,
                        '"Срещата започна в десет, но аз закъснях." means "The meeting started at ten, but I was late." The whole story is in the past.'),
                    self::trueFalse('True or false: Zakasnyah', '"Закъснях" means "I was early".', false,
                        '"Закъснях" means "I was late". "I was early" is "Подраних".'),
                    self::pairs('Match the office things', 'Match each office item to its Bulgarian word.', [
                        ['Printer', 'принтер'],
                        ['Computer', 'компютър'],
                        ['Folder', 'папка'],
                        ['Desk', 'бюро'],
                        ['Phone', 'телефон'],
                    ]),
                    self::fill('The boss asked me', 'Шефът __ помоли да остана до късно.', ['ми', 'мен', 'ме'], 2,
                        '"Шефът ме помоли да остана до късно." means "The boss asked me to stay late." Помолям takes a direct object, so "me" is ме.'),
                ],
            ],
            [
                'name' => 'Nature and Travel',
                'description' => 'Mountains, rivers and lakes of Bulgaria, compared with "от" and "отколкото".',
                'exercises' => [
                    self::pairs('Match the landscape', 'Match each English word to its Bulgarian equivalent.', [
                        ['Mountain', 'планина'],
                        ['River', 'река'],
                        ['Lake', 'езеро'],
                        ['Forest', 'гора'],
                        ['Peak', 'връх'],
                    ]),
                    self::fill('Longer than the Iskar', 'Дунав е по-дълъг __ Искър.', ['от', 'с', 'за'], 0,
                        '"Дунав е по-дълъг от Искър." means "The Danube is longer than the Iskar." "Than" before a noun is от.'),
                    self::trueFalse('True or false: Cherno more', '"Черно море" lies on the eastern side of Bulgaria.', true,
                        'Correct. The Black Sea coast is Bulgaria\'s eastern border.'),
                    self::fill('The highest mountain', 'Рила е __ планина в България.', ['по-високата', 'най-високата', 'високият'], 1,
                        '"Рила е най-високата планина в България." means "Rila is the highest mountain in Bulgaria."'),
                    self::trueFalse('True or false: Kolkoto poveche', '"Колкото повече, толкова по-добре" means "The less, the better".', false,
                        'It means "The more, the better". "Колкото ..., толкова ..." links two comparisons.'),
                    self::pairs('Match the animals', 'Match each animal to its Bulgarian word.', [
                        ['Bear', 'мечка'],
                        ['Wolf', 'вълк'],
                        ['Fox', 'лисица'],
                        ['Eagle', 'орел'],
                        ['Deer', 'елен'],
                    ]),
                    self::fill('Warmer than the Baltic', 'Черно море е по-топло __ Балтийско море.', ['отколкото', 'от', 'с'], 1,
                        '"Черно море е по-топло от Балтийско море." means "The Black Sea is warmer than the Baltic Sea." Before a noun, "than" is от.'),
                ],
            ],
            [
                'name' => 'Weather and Time',
                'description' => 'Describe the weather and say how long ago something happened.',
                'exercises' => [
                    self::pairs('Match the weather', 'Match each English phrase to its Bulgarian equivalent.', [
                        ["It's raining", 'Вали дъжд'],
                        ["It's snowing", 'Вали сняг'],
                        ["It's windy", 'Духа вятър'],
                        ["It's sunny", 'Слънчево е'],
                        ["It's cloudy", 'Облачно е'],
                    ]),
                    self::fill('Take an umbrella', 'Вземи чадър, навън __ дъжд.', ['духа', 'вали', 'грее'], 1,
                        '"Вземи чадър, навън вали дъжд." means "Take an umbrella, it is raining outside."'),
                    self::trueFalse('True or false: Vali', 'Weather verbs like "вали" need no subject: you just say "Вали."', true,
                        'Correct. "Вали" alone means "It is raining". Bulgarian has no "it" here.'),
                    self::fill('A week ago', 'Преди една __ беше много горещо.', ['седмици', 'седмицата', 'седмица'], 2,
                        '"Преди една седмица беше много горещо." means "A week ago it was very hot." Преди + a length of time means "ago".'),
                    self::trueFalse('True or false: Prognozata', '"Прогнозата за утре" means "yesterday\'s forecast".', false,
                        'It means "the forecast for tomorrow". Утре is tomorrow.'),
                    self::pairs('Match the temperatures', 'Match each English word to its Bulgarian equivalent.', [
                        ['Warm', 'топло'],
                        ['Cool', 'хладно'],
                        ['Freezing', 'мразовито'],
                        ['Humid', 'влажно'],
                        ['Dry', 'сухо'],
                    ]),
                    self::fill('It rained all day', 'Вчера __ целия ден и улиците бяха мокри.', ['вали', 'ще вали', 'валя'], 2,
                        '"Вчера валя целия ден..." means "Yesterday it rained all day, and the streets were wet." Валя is the past tense of вали.'),
                ],
            ],
            [
                'name' => 'Celebrations',
                'description' => 'Wishes for birthdays, name days, Christmas and Easter.',
                'exercises' => [
                    self::pairs('Match the wishes', 'Match each English wish to its Bulgarian equivalent.', [
                        ['Happy birthday!', 'Честит рожден ден!'],
                        ['Merry Christmas!', 'Весела Коледа!'],
                        ['Happy New Year!', 'Честита Нова година!'],
                        ['Happy name day!', 'Честит имен ден!'],
                        ['Christ is risen!', 'Христос воскресе!'],
                    ]),
                    self::fill('Easter eggs', 'За Великден боядисваме __ в червено.', ['яйца', 'елха', 'торта'], 0,
                        '"За Великден боядисваме яйца в червено." means "At Easter we dye eggs red."'),
                    self::trueFalse('True or false: Imen den', 'A name day (имен ден) is celebrated on the day of the saint you are named after.', true,
                        'Correct. For example, people named Георги celebrate on Гергьовден, 6 May.'),
                    self::fill('Christmas Eve table', 'На Бъдни вечер на масата има нечетен брой __ без месо.', ['чаши', 'гости', 'ястия'], 2,
                        '"На Бъдни вечер на масата има нечетен брой ястия без месо." means "On Christmas Eve there is an odd number of meatless dishes on the table."'),
                    self::trueFalse('True or false: Easter reply', 'The reply to "Христос воскресе!" is "Честит рожден ден!"', false,
                        'The reply is "Воистина воскресе!", meaning "He is risen indeed!".'),
                    self::pairs('Match the party words', 'Match each English word to its Bulgarian equivalent.', [
                        ['Present', 'подарък'],
                        ['Guest', 'гост'],
                        ['Candle', 'свещ'],
                        ['Invitation', 'покана'],
                        ['Party', 'парти'],
                    ]),
                    self::fill('Decorating the tree', 'На Коледа украсяваме __ в хола.', ['яйца', 'елха', 'агне'], 1,
                        '"На Коледа украсяваме елха в хола." means "At Christmas we decorate a tree in the living room."'),
                ],
            ],
        ];
    }
}
