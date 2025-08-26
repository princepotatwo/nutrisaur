package com.example.nutrisaur11;

import android.content.Context;
import android.graphics.drawable.Drawable;
import android.util.Log;
import java.util.HashMap;
import java.util.Map;

public class FoodImageHelper {
    // Image resource ID cache to prevent repeated lookups
    private static final Map<String, Integer> imageResourceCache = new HashMap<>();
    private static final Map<String, String> dishToImageMap = new HashMap<>();
    private static boolean isInitialized = false;
    
    public static void initialize(Context context) {
        if (isInitialized) return;
        
        // Map dish names to their corresponding image files
        // Main dishes
        dishToImageMap.put("Adobo", "adobo.jpg");
        dishToImageMap.put("Sinigang na Baboy", "sinigang_na_baboy.jpg");
        dishToImageMap.put("Sinigang na Hipon", "sinigang_na_hipon.jpg");
        dishToImageMap.put("Sinigang na Isda", "sinigang_na_isda.png");
        dishToImageMap.put("Nilagang Baka", "nilagang_baka.jpg");
        dishToImageMap.put("Nilagang Baboy", "nilagang_baboy.jpg");
        dishToImageMap.put("Tinola", "tinola.jpg");
        dishToImageMap.put("Bulalo", "bulalo.jpg");
        dishToImageMap.put("Dinengdeng", "dinengdeng.jpg");
        dishToImageMap.put("Lauya", "lauya.jpg");
        dishToImageMap.put("Kare-kare", "kare_kare.jpg");
        dishToImageMap.put("Menudo", "menudo.jpg");
        dishToImageMap.put("Kaldereta", "kaldereta.jpg");
        dishToImageMap.put("Afritada", "afritada.jpg");
        dishToImageMap.put("Mechado", "mechado.jpg");
        dishToImageMap.put("Pochero", "pochero.png");
        dishToImageMap.put("Bicol Express", "bicol_express.jpg");
        dishToImageMap.put("Laing", "laing.jpg");
        dishToImageMap.put("Pinakbet", "pinakbet.jpg");
        dishToImageMap.put("Dinuguan", "dinuguan.png");
        dishToImageMap.put("Pinaputok na Bangus", "pinaputok_na_bangus.jpg");
        dishToImageMap.put("Ginisang Ampalaya", "ginisang_ampalaya.jpg");
        dishToImageMap.put("Ginisang Sayote", "steamed_sayote.jpg");
        dishToImageMap.put("Ginisang Repolyo", "veg");
        dishToImageMap.put("Monggo Guisado", "monggo_guisado.jpg");
        
        // Rice dishes
        dishToImageMap.put("Sinangag", "sinangag.jpg");
        dishToImageMap.put("Steamed Riced", "steamed_riced.jpg");
        dishToImageMap.put("Lugaw", "lugaw.jpg");
        dishToImageMap.put("Goto", "goto_dish.jpg");
        
        // Meat dishes
        dishToImageMap.put("Sisig", "sisig.jpg");
        dishToImageMap.put("Mushroom Sisig", "mushroom_sisig.jpg");
        dishToImageMap.put("Lechon", "lechon.jpg");
        dishToImageMap.put("Lechon Manok", "lechon_manok.jpg");
        dishToImageMap.put("Pares", "pares.jpg");
        dishToImageMap.put("Papaitan", "papaitan.jpg");
        dishToImageMap.put("Paksiw na Pata", "paksiw_na_pata.jpg");
        dishToImageMap.put("Paksiw na Bangus", "paksiw_na_bangus.jpg");
        
        // Fish dishes
        dishToImageMap.put("Tinolang Bangus", "tinolang_bangus.jpg");
        dishToImageMap.put("Pritong Tilapia", "pritong_tilapia.jpg");
        dishToImageMap.put("Pritong Galunggong", "pritong_galunggong.jpg");
        dishToImageMap.put("Pritong Bangus", "pritong_bangus.jpg");
        dishToImageMap.put("Tinapa", "tinapa.jpg");
        dishToImageMap.put("Sweet and Sour Fish", "sweet_and_sour_fish.jpg");
        
        // Noodle dishes
        dishToImageMap.put("Pansit Lomi", "pansit_lomi.jpg");
        dishToImageMap.put("Pansit Malabon", "pansit_malabon.jpg");
        dishToImageMap.put("Pansit Bihon", "pansit_bihon.jpg");
        dishToImageMap.put("Pancit Sotanghon", "pancit_sotanghon.jpg");
        dishToImageMap.put("Pancit Molo", "pancit_molo.jpg");
        dishToImageMap.put("Pancit Canton", "pancit_canton.jpg");
        dishToImageMap.put("Mami", "mami.jpg");
        dishToImageMap.put("Sopas", "sopas.jpg");
        
        // Breakfast dishes
        dishToImageMap.put("Tapsilog", "tapsilog.png");
        dishToImageMap.put("Tocilog", "tocilog.jpg");
        dishToImageMap.put("Longsilog", "longsilog.jpg");
        dishToImageMap.put("Tortang Talong", "tortang_talong.jpg");
        dishToImageMap.put("Tortang Giniling", "tortang_giniling.jpg");
        
        // Snacks and desserts
        dishToImageMap.put("Lumpiang Shanghai", "lumpiang_shanghai.jpg");
        dishToImageMap.put("Ukoy", "ukoy.jpg");
        dishToImageMap.put("Okoy", "okoy.png");
        dishToImageMap.put("Turon", "turon.jpg");
        dishToImageMap.put("Banana Cue", "banana_cue.jpg");
        dishToImageMap.put("Maruya", "snacks");
        dishToImageMap.put("Puto", "puto.png");
        dishToImageMap.put("Puto Maya", "puto_maya.jpg");
        dishToImageMap.put("Puto Bumbong", "puto_bumbong.jpg");
        dishToImageMap.put("Kutsinta", "kutsinta.jpg");
        dishToImageMap.put("Sapin-sapin", "sapin_sapin.jpg");
        dishToImageMap.put("Suman sa Lihiya", "suman_sa_lihiya.jpg");
        dishToImageMap.put("Suman sa Latik", "suman_sa_latik.jpg");
        dishToImageMap.put("Bibingka", "bibingka.jpg");
        dishToImageMap.put("Ube Bibingka", "ube_bibingka.jpg");
        dishToImageMap.put("Ube Halaya", "ube_halaya.jpg");
        dishToImageMap.put("Leche Flan", "leche_flan.jpg");
        dishToImageMap.put("Maja Blanca", "majablanca.jpg");
        dishToImageMap.put("Pichi Pichi", "pichi_pichi.jpg");
        dishToImageMap.put("Palitaw", "palitaw.jpg");
        dishToImageMap.put("Nilupak", "nilupak.jpg");
        dishToImageMap.put("Moron", "moron.jpg");
        dishToImageMap.put("Moche", "moche.jpg");
        dishToImageMap.put("Salukara", "salukara.jpg");
        dishToImageMap.put("Tupig", "tupig.jpg");
        dishToImageMap.put("Otap", "otap.jpg");
        dishToImageMap.put("Espasol", "espasol.jpg");
        dishToImageMap.put("Panyalam", "panyalam.jpg");
        dishToImageMap.put("Twin Sticks", "twin_sticks.jpg");
        
        // Vegetables and sides
        dishToImageMap.put("Steamed Kalabasa", "steamed_kalabasa.jpg");
        dishToImageMap.put("Nilagang Kamote", "nilagang_kamote.jpg");
        dishToImageMap.put("Nilagang Saging na Saba", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Nilagang Mani", "nilagang_mani.jpg");
        
        // Beverages
        dishToImageMap.put("Buko Juice", "buko_juice.jpg");
        dishToImageMap.put("Mango Shake", "mango_shake.jpg");
        dishToImageMap.put("Sago at Gulaman", "sago_at_gulaman.jpg");
        dishToImageMap.put("Mais con Yelo", "mais_con_yelo.jpg");
        dishToImageMap.put("Saging con Yelo", "saging_con_yelo.jpg");
        dishToImageMap.put("Salabat", "salabat.jpg");
        dishToImageMap.put("Soya Milk", "soya_milk.jpg");
        
        // Other items
        dishToImageMap.put("Marie Biscuit", "marie_biscuit.jpg");
        dishToImageMap.put("Sky Flakes", "sky_flakes.jpg");
        dishToImageMap.put("Penoy", "penoy.jpg");
        dishToImageMap.put("Tokneneng", "tokneneng.jpg");
        dishToImageMap.put("Sorbetes", "sorbetes.jpg");
        dishToImageMap.put("Yakult", "yakult.jpg");
        dishToImageMap.put("Safari Chocolate Bar", "safari_chocolate_bar_this_was_my_favorite_chocolate_growing_v0_9vfimgbursfa1.jpg");
        
        // Additional dishes that might be in DishData
        dishToImageMap.put("Beef Stir Fry", "beef_stir_fry.jpg");
        dishToImageMap.put("Beef Tacos", "beef_tacos.jpg");
        dishToImageMap.put("Beef Burger", "beef_burger.jpg");
        dishToImageMap.put("Chicken Drumstick", "chicken_drumstick.jpg");
        dishToImageMap.put("Grilled Chicken Breast", "grilled_chicken_breast.jpg");
        dishToImageMap.put("Steamed Broccoli", "steamed_broccoli.jpg");
        
        // ===== NEW FILIPINO DISHES ADDED =====
        // Plant-based protein dishes
        dishToImageMap.put("Ginisang Munggo", "monggo_guisado.jpg");
        dishToImageMap.put("Tofu Sisig", "mushroom_sisig.jpg");
        dishToImageMap.put("Ginisang Togue", "steamed_sayote.jpg");
        dishToImageMap.put("Lentil Curry", "monggo_guisado.jpg");
        dishToImageMap.put("Chickpea Adobo", "adobo.jpg");
        dishToImageMap.put("Soy Chicharon", "snacks");
        dishToImageMap.put("Mung Bean Soup", "monggo_guisado.jpg");
        dishToImageMap.put("Tofu Kare-kare", "kare_kare.jpg");
        
        // More Filipino vegetarian dishes
        dishToImageMap.put("Adobong Sitaw", "adobo.jpg");
        dishToImageMap.put("Ginisang Ampalaya", "ginisang_ampalaya.jpg");
        dishToImageMap.put("Ginisang Repolyo", "ginisang_repolyo.jpg");
        dishToImageMap.put("Ginisang Sayote", "steamed_sayote.jpg");
        dishToImageMap.put("Ginisang Kalabasa", "steamed_kalabasa.jpg");
        dishToImageMap.put("Ginisang Talong", "tortang_talong.jpg");
        dishToImageMap.put("Ginisang Kangkong", "pinakbet.jpg");
        dishToImageMap.put("Ginisang Pechay", "pinakbet.jpg");
        dishToImageMap.put("Ginisang Baguio Beans", "pinakbet.jpg");
        dishToImageMap.put("Ginisang Upo", "steamed_sayote.jpg");
        
        // Filipino soups and stews
        dishToImageMap.put("Sinigang na Gulay", "sinigang_na_baboy.jpg");
        dishToImageMap.put("Bulanglang", "sinigang_na_baboy.jpg");
        dishToImageMap.put("Ginisang Munggo with Malunggay", "monggo_guisado.jpg");
        dishToImageMap.put("Ginisang Togue with Tofu", "steamed_sayote.jpg");
        dishToImageMap.put("Ginisang Patola", "steamed_sayote.jpg");
        dishToImageMap.put("Ginisang Labong", "steamed_sayote.jpg");
        dishToImageMap.put("Ginisang Gabi", "steamed_kalabasa.jpg");
        dishToImageMap.put("Ginisang Bataw", "pinakbet.jpg");
        
        // Additional missing dishes
        dishToImageMap.put("Binignit", "dessert");
        dishToImageMap.put("Daral", "dessert");
        dishToImageMap.put("Ginisang Balatong", "monggo_guisado.jpg");
        dishToImageMap.put("Ginisang Sigarilyas", "steamed_sayote.jpg");
        
        // Filipino egg dishes
        dishToImageMap.put("Tortang Talong", "tortang_talong.jpg");
        dishToImageMap.put("Tortang Ampalaya", "ginisang_ampalaya.jpg");
        dishToImageMap.put("Tortang Kamote", "nilagang_kamote.jpg");
        dishToImageMap.put("Tortang Kalabasa", "steamed_kalabasa.jpg");
        dishToImageMap.put("Tortang Togue", "steamed_sayote.jpg");
        dishToImageMap.put("Tortang Munggo", "monggo_guisado.jpg");
        dishToImageMap.put("Tortang Sitaw", "adobo.jpg");
        dishToImageMap.put("Tortang Repolyo", "veg");
        dishToImageMap.put("Tortang Sayote", "steamed_sayote.jpg");
        dishToImageMap.put("Tortang Upo", "steamed_sayote.jpg");
        
        // Filipino dairy and cheese dishes
        dishToImageMap.put("Kesong Puti with Pandesal", "puto.png");
        dishToImageMap.put("Kesong Puti with Mango", "mango_shake.jpg");
        dishToImageMap.put("Kesong Puti with Guava", "puto.png");
        dishToImageMap.put("Kesong Puti with Papaya", "puto.png");
        dishToImageMap.put("Kesong Puti with Pineapple", "puto.png");
        dishToImageMap.put("Kesong Puti with Banana", "puto.png");
        dishToImageMap.put("Kesong Puti with Avocado", "puto.png");
        dishToImageMap.put("Kesong Puti with Coconut", "puto.png");
        dishToImageMap.put("Kesong Puti with Jackfruit", "puto.png");
        dishToImageMap.put("Kesong Puti with Soursop", "puto.png");
        dishToImageMap.put("Kesong Puti with Santol", "puto.png");
        
        // More Filipino traditional dishes
        dishToImageMap.put("Ginisang Okra", "pinakbet.jpg");
        dishToImageMap.put("Ginisang Bunga ng Saging", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Ginisang Puso ng Saging", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Ginisang Dahon ng Saging", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Ginisang Kamote Tops", "nilagang_kamote.jpg");
        dishToImageMap.put("Ginisang Alugbati", "pinakbet.jpg");
        dishToImageMap.put("Ginisang Kulitis", "pinakbet.jpg");
        dishToImageMap.put("Ginisang Talbos ng Kamote", "nilagang_kamote.jpg");
        dishToImageMap.put("Ginisang Talbos ng Gabi", "steamed_kalabasa.jpg");
        
        // Filipino fruit and nut dishes
        dishToImageMap.put("Buko Pandan Salad", "buko_salad.jpg");
        dishToImageMap.put("Buko Lychee Salad", "buko_salad.jpg");
        dishToImageMap.put("Buko Mango Salad", "buko_salad.jpg");
        dishToImageMap.put("Buko Guava Salad", "buko_salad.jpg");
        dishToImageMap.put("Buko Papaya Salad", "buko_salad.jpg");
        dishToImageMap.put("Buko Pineapple Salad", "buko_salad.jpg");
        dishToImageMap.put("Buko Banana Salad", "buko_salad.jpg");
        dishToImageMap.put("Buko Avocado Salad", "buko_salad.jpg");
        dishToImageMap.put("Buko Jackfruit Salad", "buko_salad.jpg");
        dishToImageMap.put("Buko Soursop Salad", "buko_salad.jpg");
        
        // Filipino rice dishes
        dishToImageMap.put("Arroz Caldo", "lugaw.jpg");
        dishToImageMap.put("Champorado", "lugaw.jpg");
        dishToImageMap.put("Biko", "biko.jpg");
        dishToImageMap.put("Bibingka", "bibingka.jpg");
        dishToImageMap.put("Suman", "suman_sa_latik.jpg");
        dishToImageMap.put("Kutsinta", "kutsinta.jpg");
        dishToImageMap.put("Palitaw", "palitaw.jpg");
        dishToImageMap.put("Espasol", "espasol.jpg");
        dishToImageMap.put("Tupig", "tupig.jpg");
        dishToImageMap.put("Karioka", "karioka.jpg");
        dishToImageMap.put("Puto Maya", "puto_maya.jpg");
        dishToImageMap.put("Puto Seko", "puto.png");
        dishToImageMap.put("Puto Ube", "puto.png");
        dishToImageMap.put("Puto Pandan", "puto.png");
        dishToImageMap.put("Puto Cheese", "puto.png");
        dishToImageMap.put("Puto Maja", "puto.png");
        dishToImageMap.put("Puto Keso", "puto.png");
        
        // Filipino noodle dishes (Pancit)
        dishToImageMap.put("Pancit Canton", "pancit_canton.jpg");
        dishToImageMap.put("Pancit Bihon", "pansit_bihon.jpg");
        dishToImageMap.put("Pancit Malabon", "pansit_malabon.jpg");
        dishToImageMap.put("Pancit Palabok", "pansit_malabon.jpg");
        dishToImageMap.put("Pancit Sotanghon", "pancit_sotanghon.jpg");
        dishToImageMap.put("Pancit Habhab", "pansit_bihon.jpg");
        dishToImageMap.put("Pancit Miki", "pansit_lomi.jpg");
        dishToImageMap.put("Pancit Lomi", "pansit_lomi.jpg");
        dishToImageMap.put("Pancit Canton Guisado", "pancit_canton.jpg");
        dishToImageMap.put("Pancit Bihon Guisado", "pansit_bihon.jpg");
        dishToImageMap.put("Pancit Canton with Vegetables", "pancit_canton.jpg");
        dishToImageMap.put("Pancit Bihon with Vegetables", "pansit_bihon.jpg");
        dishToImageMap.put("Pancit Canton with Tofu", "pancit_canton.jpg");
        dishToImageMap.put("Pancit Bihon with Tofu", "pansit_bihon.jpg");
        dishToImageMap.put("Pancit Canton with Mushrooms", "pancit_canton.jpg");
        dishToImageMap.put("Pancit Bihon with Mushrooms", "pansit_bihon.jpg");
        dishToImageMap.put("Pancit Canton with Broccoli", "pancit_canton.jpg");
        dishToImageMap.put("Pancit Bihon with Broccoli", "pansit_bihon.jpg");
        
        // Filipino meat dishes
        dishToImageMap.put("Adobo", "adobo.jpg");
        dishToImageMap.put("Sinigang", "sinigang_na_baboy.jpg");
        dishToImageMap.put("Tinola", "tinola.jpg");
        dishToImageMap.put("Kare-kare", "kare_kare.jpg");
        dishToImageMap.put("Bulalo", "bulalo.jpg");
        dishToImageMap.put("Nilagang Baka", "nilagang_baka.jpg");
        dishToImageMap.put("Lechon Manok", "lechon_manok.jpg");
        dishToImageMap.put("Chicken Inasal", "lechon_manok.jpg");
        dishToImageMap.put("Pork BBQ", "lechon.jpg");
        dishToImageMap.put("Sisig", "sisig.jpg");
        dishToImageMap.put("Kaldereta", "kaldereta.jpg");
        dishToImageMap.put("Mechado", "mechado.jpg");
        dishToImageMap.put("Estofado", "lechon.jpg");
        dishToImageMap.put("Menudo", "menudo.jpg");
        dishToImageMap.put("Afritada", "afritada.jpg");
        dishToImageMap.put("Pochero", "pochero.png");
        dishToImageMap.put("Callos", "lechon.jpg");
        dishToImageMap.put("Bicol Express", "bicol_express.jpg");
        dishToImageMap.put("Dinuguan", "dinuguan.png");
        dishToImageMap.put("Paksiw na Pata", "paksiw_na_pata.jpg");
        dishToImageMap.put("Paksiw na Lechon", "lechon.jpg");
        dishToImageMap.put("Paksiw na Isda", "paksiw_na_bangus.jpg");
        dishToImageMap.put("Paksiw na Manok", "lechon_manok.jpg");
        dishToImageMap.put("Paksiw na Baboy", "lechon.jpg");
        dishToImageMap.put("Paksiw na Baka", "lechon.jpg");
        dishToImageMap.put("Paksiw na Kambing", "lechon.jpg");
        dishToImageMap.put("Paksiw na Kabayo", "lechon.jpg");
        dishToImageMap.put("Paksiw na Baboy Ramo", "lechon.jpg");
        dishToImageMap.put("Paksiw na Baboy Damo", "lechon.jpg");
        
        // Filipino seafood dishes
        dishToImageMap.put("Sinigang na Isda", "sinigang_na_isda.png");
        dishToImageMap.put("Tinolang Isda", "tinolang_bangus.jpg");
        dishToImageMap.put("Ginisang Isda", "pritong_bangus.jpg");
        dishToImageMap.put("Pritong Isda", "pritong_bangus.jpg");
        dishToImageMap.put("Inihaw na Isda", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Hipon", "sinigang_na_hipon.jpg");
        dishToImageMap.put("Ginisang Alimango", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Tahong", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Halaan", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Talaba", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Bangus", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Tilapia", "pritong_tilapia.jpg");
        dishToImageMap.put("Ginisang Galunggong", "pritong_galunggong.jpg");
        dishToImageMap.put("Ginisang Tamban", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Tulingan", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Tanigue", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Lapu-lapu", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Maya-maya", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Pampano", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Dalag", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Hito", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Kanduli", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Biya", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Ayungin", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Banak", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Sapsap", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Salay-salay", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Dilis", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Tawilis", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Tawilis with Kamatis", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Tawilis with Sili", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Tawilis with Bawang", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Tawilis with Sibuyas", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Tawilis with Luya", "pritong_bangus.jpg");
        
        // Filipino street food and snacks
        dishToImageMap.put("Fish Balls", "squid_balls.png");
        dishToImageMap.put("Chicken Balls", "squid_balls.png");
        dishToImageMap.put("Ukoy", "ukoy.jpg");
        dishToImageMap.put("Fresh Lumpia", "lumpiang_shanghai.jpg");
        dishToImageMap.put("Lumpiang Shanghai", "lumpiang_shanghai.jpg");
        dishToImageMap.put("Lumpiang Gulay", "lumpiang_shanghai.jpg");
        dishToImageMap.put("Lumpiang Ubod", "lumpiang_shanghai.jpg");
        dishToImageMap.put("Empanada", "vigan_empanada.jpg");
        dishToImageMap.put("Siopao", "puto.png");
        dishToImageMap.put("Karioka", "dessert");
        dishToImageMap.put("Turon", "turon.jpg");
        dishToImageMap.put("Banana Cue", "banana_cue.jpg");
        dishToImageMap.put("Camote Cue", "nilagang_kamote.jpg");
        dishToImageMap.put("Saging na Saba", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Saging na Saba with Latik", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Kamote Cue with Latik", "nilagang_kamote.jpg");
        dishToImageMap.put("Saging na Saba with Gata", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Kamote Cue with Gata", "nilagang_kamote.jpg");
        dishToImageMap.put("Saging na Saba with Asukal", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Kamote Cue with Asukal", "nilagang_kamote.jpg");
        
        // Filipino beverages and drinks
        dishToImageMap.put("Buko Juice", "buko_juice.jpg");
        dishToImageMap.put("Sago't Gulaman", "sago_at_gulaman.jpg");
        dishToImageMap.put("Gulaman", "sago_at_gulaman.jpg");
        dishToImageMap.put("Sago", "sago_at_gulaman.jpg");
        dishToImageMap.put("Buko Pandan Juice", "buko_juice.jpg");
        dishToImageMap.put("Buko Lychee Juice", "buko_juice.jpg");
        dishToImageMap.put("Buko Mango Juice", "buko_juice.jpg");
        dishToImageMap.put("Buko Guava Juice", "buko_juice.jpg");
        dishToImageMap.put("Buko Papaya Juice", "buko_juice.jpg");
        dishToImageMap.put("Buko Pineapple Juice", "buko_juice.jpg");
        dishToImageMap.put("Buko Banana Juice", "buko_juice.jpg");
        dishToImageMap.put("Buko Avocado Juice", "buko_juice.jpg");
        dishToImageMap.put("Buko Jackfruit Juice", "buko_juice.jpg");
        dishToImageMap.put("Buko Soursop Juice", "buko_juice.jpg");
        dishToImageMap.put("Buko Santol Juice", "buko_juice.jpg");
        dishToImageMap.put("Mango Juice", "mango_shake.jpg");
        dishToImageMap.put("Guava Juice", "buko_juice.jpg");
        dishToImageMap.put("Papaya Juice", "buko_juice.jpg");
        dishToImageMap.put("Pineapple Juice", "buko_juice.jpg");
        dishToImageMap.put("Banana Juice", "buko_juice.jpg");
        dishToImageMap.put("Avocado Juice", "buko_juice.jpg");
        dishToImageMap.put("Jackfruit Juice", "buko_juice.jpg");
        dishToImageMap.put("Soursop Juice", "buko_juice.jpg");
        dishToImageMap.put("Santol Juice", "buko_juice.jpg");
        
        // Filipino fruit dishes
        dishToImageMap.put("Fresh Mango", "mango_shake.jpg");
        dishToImageMap.put("Fresh Guava", "buko_juice.jpg");
        dishToImageMap.put("Fresh Papaya", "buko_juice.jpg");
        dishToImageMap.put("Fresh Pineapple", "buko_juice.jpg");
        dishToImageMap.put("Fresh Banana", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Fresh Avocado", "buko_juice.jpg");
        dishToImageMap.put("Fresh Jackfruit", "buko_juice.jpg");
        dishToImageMap.put("Fresh Soursop", "buko_juice.jpg");
        dishToImageMap.put("Fresh Santol", "buko_juice.jpg");
        dishToImageMap.put("Fresh Rambutan", "buko_juice.jpg");
        dishToImageMap.put("Fresh Lanzones", "buko_juice.jpg");
        dishToImageMap.put("Fresh Chico", "buko_juice.jpg");
        dishToImageMap.put("Fresh Duhat", "buko_juice.jpg");
        dishToImageMap.put("Fresh Makopa", "buko_juice.jpg");
        dishToImageMap.put("Fresh Atis", "buko_juice.jpg");
        dishToImageMap.put("Fresh Guyabano", "buko_juice.jpg");
        dishToImageMap.put("Fresh Langka", "buko_juice.jpg");
        dishToImageMap.put("Fresh Saging na Saba", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Fresh Saging na Latundan", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Fresh Saging na Lakatan", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Fresh Saging na Cavendish", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Fresh Saging na Senorita", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Fresh Saging na Inabaniko", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Fresh Saging na Tundan", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Fresh Saging na Bungulan", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Fresh Saging na Saba with Latik", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Fresh Saging na Saba with Gata", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Fresh Saging na Saba with Asukal", "nilagang_saging_na_saba.png");
        
        // Filipino breakfast dishes
        dishToImageMap.put("Tapsilog", "tapsilog.png");
        dishToImageMap.put("Longsilog", "longsilog.jpg");
        dishToImageMap.put("Tocilog", "tocilog.jpg");
        dishToImageMap.put("Spamsilog", "lechon.jpg");
        dishToImageMap.put("Hotsilog", "lechon.jpg");
        dishToImageMap.put("Bangsilog", "pritong_bangus.jpg");
        dishToImageMap.put("Chicksilog", "lechon_manok.jpg");
        dishToImageMap.put("Cornsilog", "lechon.jpg");
        dishToImageMap.put("Dangsilog", "pritong_bangus.jpg");
        dishToImageMap.put("Sardinasilog", "pritong_bangus.jpg");
        dishToImageMap.put("Tuyosilog", "pritong_bangus.jpg");
        dishToImageMap.put("Tocilog with Kamatis", "lechon.jpg");
        dishToImageMap.put("Longsilog with Kamatis", "lechon.jpg");
        dishToImageMap.put("Tapsilog with Kamatis", "lechon.jpg");
        dishToImageMap.put("Spamsilog with Kamatis", "lechon.jpg");
        dishToImageMap.put("Hotsilog with Kamatis", "lechon.jpg");
        dishToImageMap.put("Bangsilog with Kamatis", "pritong_bangus.jpg");
        dishToImageMap.put("Chicksilog with Kamatis", "lechon_manok.jpg");
        dishToImageMap.put("Cornsilog with Kamatis", "lechon.jpg");
        dishToImageMap.put("Dangsilog with Kamatis", "pritong_bangus.jpg");
        dishToImageMap.put("Sardinasilog with Kamatis", "pritong_bangus.jpg");
        dishToImageMap.put("Tuyosilog with Kamatis", "pritong_bangus.jpg");
        
        // Filipino condiments and sauces
        dishToImageMap.put("Soy Sauce", "buko_juice.jpg");
        dishToImageMap.put("Vinegar", "buko_juice.jpg");
        dishToImageMap.put("Fish Sauce", "buko_juice.jpg");
        dishToImageMap.put("Calamansi", "buko_juice.jpg");
        dishToImageMap.put("Bagoong", "buko_juice.jpg");
        dishToImageMap.put("Patis", "buko_juice.jpg");
        dishToImageMap.put("Suka", "buko_juice.jpg");
        dishToImageMap.put("Toyo", "buko_juice.jpg");
        dishToImageMap.put("Kalamansi", "buko_juice.jpg");
        dishToImageMap.put("Bagoong Alamang", "buko_juice.jpg");
        dishToImageMap.put("Bagoong Isda", "buko_juice.jpg");
        dishToImageMap.put("Bagoong Balayan", "buko_juice.jpg");
        dishToImageMap.put("Bagoong Monamon", "buko_juice.jpg");
        dishToImageMap.put("Bagoong Terong", "buko_juice.jpg");
        dishToImageMap.put("Bagoong Alamang with Sili", "buko_juice.jpg");
        dishToImageMap.put("Bagoong Alamang with Kamatis", "buko_juice.jpg");
        dishToImageMap.put("Bagoong Alamang with Sibuyas", "buko_juice.jpg");
        dishToImageMap.put("Bagoong Alamang with Bawang", "buko_juice.jpg");
        dishToImageMap.put("Bagoong Alamang with Luya", "buko_juice.jpg");
        
        // ===== COMPREHENSIVE MAPPING FOR ALL DISHES =====
        // Map remaining dishes that don't have specific images
        
        // Additional main dishes
        dishToImageMap.put("Batchoy", "mami.jpg");
        dishToImageMap.put("Arroz a la Cubana", "arroz_caldo.jpg");
        dishToImageMap.put("Dinamita", "snacks");
        dishToImageMap.put("Kikiam", "fish_balls.jpg");
        dishToImageMap.put("Isaw", "isaw.jpg");
        dishToImageMap.put("Adidas", "adidas.jpg");
        dishToImageMap.put("Betamax", "betamax.jpg");
        dishToImageMap.put("Chicharon Bulaklak", "chicharon.jpg");
        dishToImageMap.put("Chicharon", "chicharon.jpg");
        dishToImageMap.put("Balut", "balut.jpg");
        dishToImageMap.put("Kwek-kwek", "kwek_kwek.jpg");
        dishToImageMap.put("Ginanggang", "ginanggang.jpg");
        dishToImageMap.put("Embutido", "embutido.jpg");
        dishToImageMap.put("Crispy Pata", "crispy_pata.jpg");
        dishToImageMap.put("Lechon Kawali", "lechon.jpg");
        dishToImageMap.put("Inihaw na Baboy", "lechon.jpg");
        dishToImageMap.put("Inihaw na Pusit", "inihaw_na_pusit.jpg");
        dishToImageMap.put("Inihaw na Liempo", "lechon.jpg");
        dishToImageMap.put("Daing na Bangus", "pritong_bangus.jpg");
        dishToImageMap.put("Adobo sa Gata", "adobo.jpg");
        dishToImageMap.put("Escabeche", "sweet_and_sour_fish.jpg");
        dishToImageMap.put("Sweet and Sour Pork", "meat");
        dishToImageMap.put("Soup No. 5", "veg");
        dishToImageMap.put("Kinilaw", "kinilaw.jpg");
        dishToImageMap.put("Kinilaw na Scallops", "kinilaw.jpg");
        
        // Additional rice dishes
        dishToImageMap.put("Steamed Riced", "steamed_riced.jpg");
        
        // Additional desserts and snacks
        dishToImageMap.put("Biko", "biko.jpg");
        
        // ===== NEW FILIPINO DISHES - COMPLETE MAPPING =====
        // Plant-based protein dishes (already mapped above)
        
        // Additional Filipino vegetarian dishes
        dishToImageMap.put("Ginisang Okra", "pinakbet.jpg");
        dishToImageMap.put("Ginisang Bunga ng Saging", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Ginisang Puso ng Saging", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Ginisang Dahon ng Saging", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Ginisang Kamote Tops", "nilagang_kamote.jpg");
        dishToImageMap.put("Ginisang Alugbati", "pinakbet.jpg");
        dishToImageMap.put("Ginisang Kulitis", "pinakbet.jpg");
        dishToImageMap.put("Ginisang Talbos ng Gabi", "steamed_kalabasa.jpg");
        
        // Additional Filipino fruit and nut dishes
        dishToImageMap.put("Buko Santol Salad", "buko_salad.jpg");
        
        // Additional Filipino rice dishes
        dishToImageMap.put("Puto Bumbong", "puto_bumbong.jpg");
        dishToImageMap.put("Puto Maya", "puto_maya.jpg");
        dishToImageMap.put("Puto Seko", "puto.png");
        dishToImageMap.put("Puto Ube", "puto.png");
        dishToImageMap.put("Puto Pandan", "puto.png");
        dishToImageMap.put("Puto Cheese", "puto.png");
        dishToImageMap.put("Puto Maja", "puto.png");
        dishToImageMap.put("Puto Keso", "puto.png");
        
        // Additional Filipino noodle dishes
        dishToImageMap.put("Pancit Habhab", "pansit_bihon.jpg");
        dishToImageMap.put("Pancit Miki", "pansit_lomi.jpg");
        dishToImageMap.put("Pancit Canton Guisado", "pancit_canton.jpg");
        dishToImageMap.put("Pancit Bihon Guisado", "pansit_bihon.jpg");
        dishToImageMap.put("Pancit Canton with Vegetables", "pancit_canton.jpg");
        dishToImageMap.put("Pancit Bihon with Vegetables", "pansit_bihon.jpg");
        dishToImageMap.put("Pancit Canton with Tofu", "pancit_canton.jpg");
        dishToImageMap.put("Pancit Bihon with Tofu", "pansit_bihon.jpg");
        dishToImageMap.put("Pancit Canton with Mushrooms", "pancit_canton.jpg");
        dishToImageMap.put("Pancit Bihon with Mushrooms", "pansit_bihon.jpg");
        dishToImageMap.put("Pancit Canton with Broccoli", "pancit_canton.jpg");
        dishToImageMap.put("Pancit Bihon with Broccoli", "pansit_bihon.jpg");
        
        // Additional Filipino meat dishes
        dishToImageMap.put("Estofado", "lechon.jpg");
        dishToImageMap.put("Callos", "lechon.jpg");
        dishToImageMap.put("Paksiw na Lechon", "lechon.jpg");
        dishToImageMap.put("Paksiw na Manok", "lechon_manok.jpg");
        dishToImageMap.put("Paksiw na Baboy", "lechon.jpg");
        dishToImageMap.put("Paksiw na Baka", "lechon.jpg");
        dishToImageMap.put("Paksiw na Kambing", "lechon.jpg");
        dishToImageMap.put("Paksiw na Kabayo", "lechon.jpg");
        dishToImageMap.put("Paksiw na Baboy Ramo", "lechon.jpg");
        dishToImageMap.put("Paksiw na Baboy Damo", "lechon.jpg");
        
        // Additional Filipino seafood dishes
        dishToImageMap.put("Ginisang Tamban", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Tulingan", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Tanigue", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Lapu-lapu", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Maya-maya", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Pampano", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Dalag", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Hito", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Kanduli", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Biya", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Ayungin", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Banak", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Sapsap", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Salay-salay", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Dilis", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Tawilis", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Tawilis with Kamatis", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Tawilis with Sili", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Tawilis with Bawang", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Tawilis with Sibuyas", "pritong_bangus.jpg");
        dishToImageMap.put("Ginisang Tawilis with Luya", "pritong_bangus.jpg");
        
        // Additional Filipino street food and snacks
        dishToImageMap.put("Lumpiang Gulay", "lumpiang_shanghai.jpg");
        dishToImageMap.put("Lumpiang Ubod", "lumpiang_shanghai.jpg");
        dishToImageMap.put("Saging na Saba with Latik", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Kamote Cue with Latik", "nilagang_kamote.jpg");
        dishToImageMap.put("Saging na Saba with Gata", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Kamote Cue with Gata", "nilagang_kamote.jpg");
        dishToImageMap.put("Saging na Saba with Asukal", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Kamote Cue with Asukal", "nilagang_kamote.jpg");
        
        // Additional Filipino beverages and drinks
        dishToImageMap.put("Buko Pandan Juice", "buko_juice.jpg");
        dishToImageMap.put("Buko Lychee Juice", "buko_juice.jpg");
        dishToImageMap.put("Buko Mango Juice", "buko_juice.jpg");
        dishToImageMap.put("Buko Guava Juice", "buko_juice.jpg");
        dishToImageMap.put("Buko Papaya Juice", "buko_juice.jpg");
        dishToImageMap.put("Buko Pineapple Juice", "buko_juice.jpg");
        dishToImageMap.put("Buko Banana Juice", "buko_juice.jpg");
        dishToImageMap.put("Buko Avocado Juice", "buko_juice.jpg");
        dishToImageMap.put("Buko Jackfruit Juice", "buko_juice.jpg");
        dishToImageMap.put("Buko Soursop Juice", "buko_juice.jpg");
        dishToImageMap.put("Buko Santol Juice", "buko_juice.jpg");
        dishToImageMap.put("Mango Juice", "mango_shake.jpg");
        dishToImageMap.put("Guava Juice", "buko_juice.jpg");
        dishToImageMap.put("Papaya Juice", "buko_juice.jpg");
        dishToImageMap.put("Pineapple Juice", "buko_juice.jpg");
        dishToImageMap.put("Banana Juice", "buko_juice.jpg");
        dishToImageMap.put("Avocado Juice", "buko_juice.jpg");
        dishToImageMap.put("Jackfruit Juice", "buko_juice.jpg");
        dishToImageMap.put("Soursop Juice", "buko_juice.jpg");
        dishToImageMap.put("Santol Juice", "buko_juice.jpg");
        
        // Additional Filipino fruit dishes
        dishToImageMap.put("Fresh Guava", "buko_juice.jpg");
        dishToImageMap.put("Fresh Papaya", "buko_juice.jpg");
        dishToImageMap.put("Fresh Pineapple", "buko_juice.jpg");
        dishToImageMap.put("Fresh Avocado", "buko_juice.jpg");
        dishToImageMap.put("Fresh Jackfruit", "buko_juice.jpg");
        dishToImageMap.put("Fresh Soursop", "buko_juice.jpg");
        dishToImageMap.put("Fresh Santol", "buko_juice.jpg");
        dishToImageMap.put("Fresh Rambutan", "buko_juice.jpg");
        dishToImageMap.put("Fresh Lanzones", "buko_juice.jpg");
        dishToImageMap.put("Fresh Chico", "buko_juice.jpg");
        dishToImageMap.put("Fresh Duhat", "buko_juice.jpg");
        dishToImageMap.put("Fresh Makopa", "buko_juice.jpg");
        dishToImageMap.put("Fresh Atis", "buko_juice.jpg");
        dishToImageMap.put("Fresh Guyabano", "buko_juice.jpg");
        dishToImageMap.put("Fresh Langka", "buko_juice.jpg");
        dishToImageMap.put("Fresh Saging na Latundan", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Fresh Saging na Lakatan", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Fresh Saging na Cavendish", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Fresh Saging na Senorita", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Fresh Saging na Inabaniko", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Fresh Saging na Tundan", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Fresh Saging na Bungulan", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Fresh Saging na Saba with Latik", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Fresh Saging na Saba with Gata", "nilagang_saging_na_saba.png");
        dishToImageMap.put("Fresh Saging na Saba with Asukal", "nilagang_saging_na_saba.png");
        
        // Additional Filipino breakfast dishes
        dishToImageMap.put("Spamsilog", "lechon.jpg");
        dishToImageMap.put("Hotsilog", "lechon.jpg");
        dishToImageMap.put("Bangsilog", "pritong_bangus.jpg");
        dishToImageMap.put("Chicksilog", "lechon_manok.jpg");
        dishToImageMap.put("Cornsilog", "lechon.jpg");
        dishToImageMap.put("Dangsilog", "pritong_bangus.jpg");
        dishToImageMap.put("Sardinasilog", "pritong_bangus.jpg");
        dishToImageMap.put("Tuyosilog", "pritong_bangus.jpg");
        dishToImageMap.put("Tocilog with Kamatis", "lechon.jpg");
        dishToImageMap.put("Longsilog with Kamatis", "lechon.jpg");
        dishToImageMap.put("Tapsilog with Kamatis", "lechon.jpg");
        dishToImageMap.put("Spamsilog with Kamatis", "lechon.jpg");
        dishToImageMap.put("Hotsilog with Kamatis", "lechon.jpg");
        dishToImageMap.put("Bangsilog with Kamatis", "pritong_bangus.jpg");
        dishToImageMap.put("Chicksilog with Kamatis", "lechon_manok.jpg");
        dishToImageMap.put("Cornsilog with Kamatis", "lechon.jpg");
        dishToImageMap.put("Dangsilog with Kamatis", "pritong_bangus.jpg");
        dishToImageMap.put("Sardinasilog with Kamatis", "pritong_bangus.jpg");
        dishToImageMap.put("Tuyosilog with Kamatis", "pritong_bangus.jpg");
        
        // Additional Filipino condiments and sauces
        dishToImageMap.put("Soy Sauce", "buko_juice.jpg");
        dishToImageMap.put("Vinegar", "buko_juice.jpg");
        dishToImageMap.put("Fish Sauce", "buko_juice.jpg");
        dishToImageMap.put("Calamansi", "buko_juice.jpg");
        dishToImageMap.put("Bagoong", "buko_juice.jpg");
        dishToImageMap.put("Patis", "buko_juice.jpg");
        dishToImageMap.put("Suka", "buko_juice.jpg");
        dishToImageMap.put("Toyo", "buko_juice.jpg");
        dishToImageMap.put("Kalamansi", "buko_juice.jpg");
        dishToImageMap.put("Bagoong Alamang", "buko_juice.jpg");
        dishToImageMap.put("Bagoong Isda", "buko_juice.jpg");
        dishToImageMap.put("Bagoong Balayan", "buko_juice.jpg");
        dishToImageMap.put("Bagoong Monamon", "buko_juice.jpg");
        dishToImageMap.put("Bagoong Terong", "buko_juice.jpg");
        dishToImageMap.put("Bagoong Alamang with Sili", "buko_juice.jpg");
        dishToImageMap.put("Bagoong Alamang with Kamatis", "buko_juice.jpg");
        dishToImageMap.put("Bagoong Alamang with Sibuyas", "buko_juice.jpg");
        dishToImageMap.put("Bagoong Alamang with Bawang", "buko_juice.jpg");
        dishToImageMap.put("Bagoong Alamang with Luya", "buko_juice.jpg");
        
        // ===== FALLBACK IMAGES FOR ANY REMAINING DISHES =====
        // These will be used for any dishes not explicitly mapped above
        
        isInitialized = true;
        Log.d("FoodImageHelper", "Initialized with " + dishToImageMap.size() + " dish-image mappings");
    }
    
    public static String getImageFileName(String dishName) {
        if (!isInitialized) {
            Log.w("FoodImageHelper", "FoodImageHelper not initialized. Call initialize() first.");
            return null;
        }
        
        // First, try to get the exact mapping
        String imageFileName = dishToImageMap.get(dishName);
        if (imageFileName != null) {
            Log.d("FoodImageHelper", "Exact mapping found for " + dishName + ": " + imageFileName);
            return imageFileName;
        }
        
        // If no exact mapping, try to find a smart fallback based on dish characteristics
        imageFileName = getSmartFallbackImage(dishName);
        if (imageFileName != null) {
            Log.d("FoodImageHelper", "Using smart fallback for " + dishName + ": " + imageFileName);
            return imageFileName;
        }
        
        // Last resort: use category-based default
        imageFileName = getCategoryDefaultImage(dishName);
        if (imageFileName != null) {
            Log.d("FoodImageHelper", "Using category default for " + dishName + ": " + imageFileName);
            return imageFileName;
        }
        
        // Try enhanced fallback for specific dishes
        imageFileName = getEnhancedFallbackImage(dishName);
        if (imageFileName != null) {
            Log.d("FoodImageHelper", "Using enhanced fallback for " + dishName + ": " + imageFileName);
            return imageFileName;
        }
        
                    // Ultimate fallback - this should never happen now
            Log.w("FoodImageHelper", "WARNING: No image found for " + dishName + " - using default_img.png as ultimate fallback!");
            return "default_img";
    }
    
    /**
     * Get a smart fallback image based on dish name analysis
     */
    private static String getSmartFallbackImage(String dishName) {
        String lowerName = dishName.toLowerCase();
        
        // Meat-based dishes
        if (lowerName.contains("pork") || lowerName.contains("baboy") || lowerName.contains("lechon")) {
            return "meat";
        }
        if (lowerName.contains("beef") || lowerName.contains("baka")) {
            return "meat";
        }
        if (lowerName.contains("chicken") || lowerName.contains("manok")) {
            return "meat";
        }
        
        // Fish and seafood
        if (lowerName.contains("fish") || lowerName.contains("isda") || lowerName.contains("bangus") || 
            lowerName.contains("tilapia") || lowerName.contains("galunggong")) {
            return "meat"; // Fish is protein-rich like meat
        }
        if (lowerName.contains("shrimp") || lowerName.contains("hipon")) {
            return "meat"; // Shrimp is protein-rich
        }
        if (lowerName.contains("squid") || lowerName.contains("pusit")) {
            return "meat"; // Squid is protein-rich
        }
        
        // Vegetables
        if (lowerName.contains("vegetable") || lowerName.contains("gulay") || lowerName.contains("ampalaya") ||
            lowerName.contains("sayote") || lowerName.contains("repolyo") || lowerName.contains("kalabasa")) {
            return "veg";
        }
        if (lowerName.contains("okra") || lowerName.contains("alugbati") || lowerName.contains("kulitis")) {
            return "veg";
        }
        
        // Rice dishes
        if (lowerName.contains("rice") || lowerName.contains("kanin") || lowerName.contains("arroz")) {
            return "snacks";
        }
        
        // Noodle dishes
        if (lowerName.contains("pancit") || lowerName.contains("pansit") || lowerName.contains("noodle")) {
            return "snacks";
        }
        
        // Soups
        if (lowerName.contains("soup") || lowerName.contains("sabaw") || lowerName.contains("tinola")) {
            return "veg";
        }
        
        // Desserts and sweets
        if (lowerName.contains("dessert") || lowerName.contains("cake") || lowerName.contains("biko") ||
            lowerName.contains("bibingka") || lowerName.contains("suman")) {
            return "dessert";
        }
        
        // Snacks
        if (lowerName.contains("snack") || lowerName.contains("lumpia") || lowerName.contains("ukoy")) {
            return "snacks";
        }
        
        // Beverages
        if (lowerName.contains("juice") || lowerName.contains("drink") || lowerName.contains("shake")) {
            return "drinks";
        }
        
        // Fruits
        if (lowerName.contains("fruit") || lowerName.contains("mango") || lowerName.contains("banana") ||
            lowerName.contains("papaya") || lowerName.contains("pineapple")) {
            return "dessert";
        }
        
        // Specific Filipino dishes that might be missed
        if (lowerName.contains("binignit") || lowerName.contains("daral") || lowerName.contains("ginataang") ||
            lowerName.contains("halo-halo") || lowerName.contains("taho")) {
            return "dessert";
        }
        
        return null;
    }
    
    /**
     * Get a category-based default image using user-provided defaults
     */
    private static String getCategoryDefaultImage(String dishName) {
        String lowerName = dishName.toLowerCase();
        
        // Use user-provided default images for categories
        if (lowerName.contains("meat") || lowerName.contains("pork") || lowerName.contains("beef") || 
            lowerName.contains("chicken") || lowerName.contains("lechon") || lowerName.contains("sisig") ||
            lowerName.contains("adobo") || lowerName.contains("kaldereta") || lowerName.contains("menudo")) {
            return "meat";
        }
        if (lowerName.contains("fish") || lowerName.contains("seafood") || lowerName.contains("isda") ||
            lowerName.contains("bangus") || lowerName.contains("tilapia") || lowerName.contains("galunggong")) {
            return "meat"; // Fish is also protein-rich like meat
        }
        if (lowerName.contains("vegetable") || lowerName.contains("gulay") || lowerName.contains("ampalaya") ||
            lowerName.contains("sayote") || lowerName.contains("repolyo") || lowerName.contains("kalabasa") ||
            lowerName.contains("pinakbet") || lowerName.contains("laing") || lowerName.contains("dinengdeng")) {
            return "veg";
        }
        if (lowerName.contains("rice") || lowerName.contains("kanin") || lowerName.contains("lugaw") ||
            lowerName.contains("goto") || lowerName.contains("arroz") || lowerName.contains("champorado")) {
            return "snacks"; // Rice dishes are often snacks/meals
        }
        if (lowerName.contains("noodle") || lowerName.contains("pancit") || lowerName.contains("mami") ||
            lowerName.contains("sopas")) {
            return "snacks"; // Noodle dishes are often snacks/meals
        }
        if (lowerName.contains("soup") || lowerName.contains("sabaw") || lowerName.contains("tinola") ||
            lowerName.contains("sinigang") || lowerName.contains("nilaga") || lowerName.contains("bulalo")) {
            return "veg"; // Soups are often vegetable-based
        }
        if (lowerName.contains("dessert") || lowerName.contains("cake") || lowerName.contains("biko") ||
            lowerName.contains("bibingka") || lowerName.contains("suman") || lowerName.contains("puto") ||
            lowerName.contains("kutsinta") || lowerName.contains("sapin") || lowerName.contains("leche")) {
            return "dessert";
        }
        if (lowerName.contains("snack") || lowerName.contains("lumpia") || lowerName.contains("ukoy") ||
            lowerName.contains("turon") || lowerName.contains("banana") || lowerName.contains("maruya") ||
            lowerName.contains("karioka") || lowerName.contains("fish") || lowerName.contains("chicharon")) {
            return "snacks";
        }
        if (lowerName.contains("juice") || lowerName.contains("drink") || lowerName.contains("shake") ||
            lowerName.contains("buko") || lowerName.contains("mango") || lowerName.contains("sago") ||
            lowerName.contains("gulaman") || lowerName.contains("mais") || lowerName.contains("salabat")) {
            return "drinks";
        }
        if (lowerName.contains("fruit") || lowerName.contains("mango") || lowerName.contains("banana") ||
            lowerName.contains("papaya") || lowerName.contains("pineapple") || lowerName.contains("guava") ||
            lowerName.contains("avocado") || lowerName.contains("jackfruit") || lowerName.contains("soursop")) {
            return "dessert"; // Fruits are often desserts
        }
        if (lowerName.contains("egg") || lowerName.contains("torta") || lowerName.contains("penoy") ||
            lowerName.contains("tokneneng") || lowerName.contains("balut")) {
            return "meat"; // Eggs are protein-rich like meat
        }
        if (lowerName.contains("dairy") || lowerName.contains("cheese") || lowerName.contains("keso") ||
            lowerName.contains("milk") || lowerName.contains("yogurt")) {
            return "meat"; // Dairy is protein-rich
        }
        
        // Ultimate fallback - use veg for unknown dishes (most Filipino dishes are vegetable-based)
        return "veg";
    }
    
    /**
     * Enhanced fallback system to ensure every dish gets an appropriate image
     */
    private static String getEnhancedFallbackImage(String dishName) {
        String lowerName = dishName.toLowerCase();
        
        // More specific fallbacks based on dish characteristics
        if (lowerName.contains("lechon") || lowerName.contains("sisig") || lowerName.contains("adobo") ||
            lowerName.contains("kaldereta") || lowerName.contains("menudo") || lowerName.contains("embutido") ||
            lowerName.contains("afritada") || lowerName.contains("mechado") || lowerName.contains("pochero")) {
            return "meat";
        }
        
        if (lowerName.contains("bangus") || lowerName.contains("tilapia") || lowerName.contains("galunggong") ||
            lowerName.contains("pusit") || lowerName.contains("hipon") || lowerName.contains("alimango")) {
            return "meat";
        }
        
        if (lowerName.contains("pancit") || lowerName.contains("mami") || lowerName.contains("sopas") ||
            lowerName.contains("bihon") || lowerName.contains("canton") || lowerName.contains("lomi") ||
            lowerName.contains("sotanghon") || lowerName.contains("molo")) {
            return "snacks";
        }
        
        if (lowerName.contains("sinangag") || lowerName.contains("lugaw") || lowerName.contains("goto") ||
            lowerName.contains("arroz") || lowerName.contains("champorado") || lowerName.contains("biko") ||
            lowerName.contains("bibingka") || lowerName.contains("suman") || lowerName.contains("puto")) {
            return "snacks";
        }
        
        if (lowerName.contains("lumpia") || lowerName.contains("ukoy") || lowerName.contains("turon") ||
            lowerName.contains("maruya") || lowerName.contains("karioka") || lowerName.contains("dinamita") ||
            lowerName.contains("chicharon") || lowerName.contains("fish balls") || lowerName.contains("squid balls")) {
            return "snacks";
        }
        
        // Specific Filipino dishes that might be missed
        if (lowerName.contains("binignit") || lowerName.contains("daral") || lowerName.contains("ginataang") ||
            lowerName.contains("halo-halo") || lowerName.contains("taho") || lowerName.contains("taho")) {
            return "dessert";
        }
        
        if (lowerName.contains("buko") || lowerName.contains("mango") || lowerName.contains("sago") ||
            lowerName.contains("gulaman") || lowerName.contains("mais") || lowerName.contains("salabat") ||
            lowerName.contains("juice") || lowerName.contains("shake")) {
            return "drinks";
        }
        
        if (lowerName.contains("mango") || lowerName.contains("banana") || lowerName.contains("papaya") ||
            lowerName.contains("pineapple") || lowerName.contains("guava") || lowerName.contains("avocado") ||
            lowerName.contains("jackfruit") || lowerName.contains("soursop")) {
            return "dessert";
        }
        
        // Default to veg for everything else
        return "veg";
    }
    
        public static int getImageResourceId(Context context, String dishName) {
        // Check cache first for performance
        if (imageResourceCache.containsKey(dishName)) {
            return imageResourceCache.get(dishName);
        }
        
        String imageFileName = getImageFileName(dishName);
        if (imageFileName == null) {
            int defaultId = R.drawable.ic_food_simple;
            imageResourceCache.put(dishName, defaultId);
            return defaultId;
        }

        // Remove file extension to get resource name
        String resourceName = imageFileName.replaceAll("\\.(jpg|png|jpeg)$", "");

        // Try to get the resource ID
        try {
            int resourceId = context.getResources().getIdentifier(resourceName, "drawable", context.getPackageName());
            if (resourceId == 0) {
                Log.w("FoodImageHelper", "Resource not found for " + resourceName + ", using default_img.png");
                // Try to get default_img.png as fallback
                int defaultResourceId = context.getResources().getIdentifier("default_img", "drawable", context.getPackageName());
                if (defaultResourceId != 0) {
                    imageResourceCache.put(dishName, defaultResourceId);
                    return defaultResourceId;
                }
                // If even default_img.png is not found, use the system icon
                int fallbackId = R.drawable.ic_food_simple;
                imageResourceCache.put(dishName, fallbackId);
                return fallbackId;
            }
            // Cache the successful result
            imageResourceCache.put(dishName, resourceId);
            return resourceId;
        } catch (Exception e) {
            Log.e("FoodImageHelper", "Error getting resource ID for " + resourceName + ": " + e.getMessage());
            // Try to get default_img.png as fallback
            try {
                int defaultResourceId = context.getResources().getIdentifier("default_img", "drawable", context.getPackageName());
                if (defaultResourceId != 0) {
                    imageResourceCache.put(dishName, defaultResourceId);
                    return defaultResourceId;
                }
            } catch (Exception defaultEx) {
                Log.e("FoodImageHelper", "Error getting default_img.png: " + defaultEx.getMessage());
            }
            // If even default_img.png is not found, use the system icon
            int fallbackId = R.drawable.ic_food_simple;
            imageResourceCache.put(dishName, fallbackId);
            return fallbackId;
        }
    }
    
    public static boolean hasImage(String dishName) {
        return dishToImageMap.containsKey(dishName);
    }
}
