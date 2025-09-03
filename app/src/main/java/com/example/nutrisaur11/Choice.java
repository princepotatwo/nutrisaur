package com.example.nutrisaur11;

public class Choice {
    private String text;
    private String description;
    private int colorResId;
    
    public Choice(String text, String description, int colorResId) {
        this.text = text;
        this.description = description;
        this.colorResId = colorResId;
    }
    
    public String getText() { return text; }
    public String getDescription() { return description; }
    public int getColorResId() { return colorResId; }
}
