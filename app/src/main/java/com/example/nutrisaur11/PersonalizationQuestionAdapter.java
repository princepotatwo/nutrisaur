package com.example.nutrisaur11;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.LinearLayout;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import java.util.List;

public class PersonalizationQuestionAdapter extends RecyclerView.Adapter<PersonalizationQuestionAdapter.QuestionViewHolder> {
    private Context context;
    private List<PersonalizationQuestion> questions;
    private OnQuestionInteractionListener listener;

    public interface OnQuestionInteractionListener {
        void onChoiceSelected(int questionIndex, Choice choice);
        void onQuestionChanged(int position);
    }

    public PersonalizationQuestionAdapter(Context context, List<PersonalizationQuestion> questions, OnQuestionInteractionListener listener) {
        this.context = context;
        this.questions = questions;
        this.listener = listener;
    }

    @NonNull
    @Override
    public QuestionViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.question_page_layout, parent, false);
        return new QuestionViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull QuestionViewHolder holder, int position) {
        PersonalizationQuestion question = questions.get(position);
        holder.bind(question, position);
    }

    @Override
    public int getItemCount() {
        return questions.size();
    }
    
    // Method to update checkbox state
    public void updateCheckboxState(int questionIndex, Choice choice, boolean isSelected) {
        // Get the current view holder and update its checkbox
        if (questionIndex >= 0 && questionIndex < questions.size()) {
            // Find the view that contains this choice and update its checkbox
            // This will be called from the activity when a choice is selected
            notifyItemChanged(questionIndex);
        }
    }

    class QuestionViewHolder extends RecyclerView.ViewHolder {
        private LinearLayout choicesContainer;

        public QuestionViewHolder(@NonNull View itemView) {
            super(itemView);
            choicesContainer = itemView.findViewById(R.id.choices_container);
        }

        public void bind(PersonalizationQuestion question, int position) {
            choicesContainer.removeAllViews();
            
            for (Choice choice : question.getChoices()) {
                View choiceView = createChoiceView(choice, question.isMultipleChoice(), position);
                choicesContainer.addView(choiceView);
            }
        }

        private View createChoiceView(Choice choice, boolean isMultipleChoice, int questionIndex) {
            if (isMultipleChoice) {
                return createMultipleChoiceView(choice, questionIndex);
            } else {
                return createSingleChoiceView(choice, questionIndex);
            }
        }

        private View createSingleChoiceView(Choice choice, int questionIndex) {
            // Create a simple card with basic layout - EXACTLY SAME AS BEFORE
            LinearLayout cardView = new LinearLayout(context);
            cardView.setLayoutParams(new LinearLayout.LayoutParams(
                (int) (context.getResources().getDisplayMetrics().widthPixels * 0.9),
                300 // Bigger card size - SAME AS BEFORE
            ));
            
            cardView.setOrientation(LinearLayout.HORIZONTAL);
            cardView.setPadding(20, 20, 20, 20);
            cardView.setBackgroundResource(R.drawable.card_background);
            cardView.setElevation(4);
            cardView.setClickable(true);
            cardView.setFocusable(true);
            cardView.setForeground(context.getResources().getDrawable(R.drawable.button_ripple));
            
            // Add margin - SAME AS BEFORE
            LinearLayout.LayoutParams params = (LinearLayout.LayoutParams) cardView.getLayoutParams();
            params.setMargins(0, 10, 0, 10);
            cardView.setLayoutParams(params);
            
            // Left side - Text content - SAME AS BEFORE
            LinearLayout leftSide = new LinearLayout(context);
            leftSide.setLayoutParams(new LinearLayout.LayoutParams(
                0,
                LinearLayout.LayoutParams.MATCH_PARENT,
                1.0f
            ));
            leftSide.setOrientation(LinearLayout.VERTICAL);
            leftSide.setGravity(android.view.Gravity.CENTER_VERTICAL);
            
            // Title - SAME AS BEFORE
            TextView title = new TextView(context);
            title.setText(choice.getText().toUpperCase());
            title.setTextColor(context.getResources().getColor(android.R.color.white));
            title.setTextSize(32);
            title.setTypeface(null, android.graphics.Typeface.BOLD);
            title.setGravity(android.view.Gravity.CENTER);
            
            // Add only title to left side - SAME AS BEFORE
            leftSide.addView(title);
            
            // Add only left side to card - SAME AS BEFORE
            cardView.addView(leftSide);
            
            // Click listener with animation - SAME AS BEFORE
            cardView.setOnClickListener(v -> {
                animateSelection(v);
                if (listener != null) {
                    listener.onChoiceSelected(questionIndex, choice);
                }
            });
            
            return cardView;
        }

        private View createMultipleChoiceView(Choice choice, int questionIndex) {
            // Create a button-style card for multiple choice
            LinearLayout cardView = new LinearLayout(context);
            cardView.setLayoutParams(new LinearLayout.LayoutParams(
                (int) (context.getResources().getDisplayMetrics().widthPixels * 0.9),
                300
            ));
            
            cardView.setOrientation(LinearLayout.HORIZONTAL);
            cardView.setPadding(20, 20, 20, 20);
            cardView.setBackgroundResource(R.drawable.card_background);
            cardView.setElevation(4);
            cardView.setClickable(true);
            cardView.setFocusable(true);
            cardView.setForeground(context.getResources().getDrawable(R.drawable.button_ripple));
            
            // Add margin
            LinearLayout.LayoutParams params = (LinearLayout.LayoutParams) cardView.getLayoutParams();
            params.setMargins(0, 10, 0, 10);
            cardView.setLayoutParams(params);
            
            // Left side - Text content
            LinearLayout leftSide = new LinearLayout(context);
            leftSide.setLayoutParams(new LinearLayout.LayoutParams(
                0,
                LinearLayout.LayoutParams.MATCH_PARENT,
                1.0f
            ));
            leftSide.setOrientation(LinearLayout.VERTICAL);
            leftSide.setGravity(android.view.Gravity.CENTER_VERTICAL);
            
            // Title
            TextView title = new TextView(context);
            title.setText(choice.getText().toUpperCase());
            title.setTextColor(context.getResources().getColor(android.R.color.white));
            title.setTextSize(32);
            title.setTypeface(null, android.graphics.Typeface.BOLD);
            title.setGravity(android.view.Gravity.CENTER);
            
            leftSide.addView(title);
            
            // Right side - Selection indicator button
            LinearLayout rightSide = new LinearLayout(context);
            rightSide.setLayoutParams(new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.WRAP_CONTENT,
                LinearLayout.LayoutParams.MATCH_PARENT
            ));
            rightSide.setGravity(android.view.Gravity.CENTER);
            
            // Create a proper button for selection
            TextView selectionButton = new TextView(context);
            selectionButton.setText("SELECT");
            selectionButton.setTextColor(context.getResources().getColor(android.R.color.white));
            selectionButton.setTextSize(18);
            selectionButton.setTypeface(null, android.graphics.Typeface.BOLD);
            selectionButton.setPadding(20, 10, 20, 10);
            selectionButton.setBackgroundResource(R.drawable.button_primary_background);
            selectionButton.setId(View.generateViewId());
            rightSide.addView(selectionButton);
            
            cardView.addView(leftSide);
            cardView.addView(rightSide);
            
            // Click listener with animation
            cardView.setOnClickListener(v -> {
                animateSelection(v);
                if (listener != null) {
                    listener.onChoiceSelected(questionIndex, choice);
                }
            });
            
            return cardView;
        }

        private void animateSelection(View view) {
            // SAME ANIMATION AS BEFORE
            view.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .alpha(0.8f)
                .setDuration(100)
                .withEndAction(() -> {
                    view.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .alpha(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
        }
    }
}
