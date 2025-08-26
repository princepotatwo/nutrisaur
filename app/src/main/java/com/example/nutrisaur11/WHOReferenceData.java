package com.example.nutrisaur11;

public class WHOReferenceData {
    // Example reference data for demonstration (real tables are much larger)
    // Format: [ageMonths][0]=median, [ageMonths][1]=SD
    private static final double[][] WFA_BOY = {
        {3.3, 0.5},   // 0 months
        {5.0, 0.6},   // 2 months
        {6.4, 0.7},   // 4 months
        {7.4, 0.8},   // 6 months
        {8.6, 0.9},   // 9 months
        {9.6, 1.0},   // 12 months
        {10.2, 1.1},  // 18 months
        {12.2, 1.1},  // 24 months
        {13.7, 1.2},  // 36 months
        {15.3, 1.3},  // 48 months
        {16.3, 1.4}   // 60 months
    };
    private static final double[][] WFA_GIRL = {
        {3.2, 0.5}, {4.5, 0.6}, {5.8, 0.7}, {6.7, 0.8}, {7.7, 0.9}, {8.7, 1.0}, {9.7, 1.1}, {11.5, 1.05}, {13.0, 1.2}, {14.2, 1.3}, {15.2, 1.4}
    };
    private static final double[][] HFA_BOY = {
        {49.9, 1.9}, {58.4, 2.0}, {65.9, 2.1}, {69.2, 2.2}, {72.6, 2.3}, {76.1, 2.4}, {80.7, 2.5}, {86.4, 3.2}, {95.2, 3.5}, {102.7, 3.7}, {109.2, 3.9}
    };
    private static final double[][] HFA_GIRL = {
        {49.1, 1.8}, {57.1, 1.9}, {64.3, 2.0}, {67.3, 2.1}, {70.7, 2.2}, {74.0, 2.3}, {78.6, 2.4}, {85.0, 3.1}, {94.2, 3.4}, {101.4, 3.6}, {108.0, 3.8}
    };
    // For WFH, use height as the index
    private static final double[][] WFH_BOY = {
        {2.5, 0.2},   // 45cm
        {3.3, 0.3},   // 50cm
        {4.5, 0.4},   // 55cm
        {5.7, 0.5},   // 60cm
        {7.0, 0.6},   // 65cm
        {8.4, 0.7},   // 70cm
        {9.7, 0.8},   // 75cm
        {11.1, 0.9},  // 80cm
        {12.2, 1.1},  // 86cm
        {13.5, 1.2},  // 92cm
        {15.0, 1.3}   // 98cm
    };
    private static final double[][] WFH_GIRL = {
        {2.4, 0.2}, {3.2, 0.3}, {4.3, 0.4}, {5.5, 0.5}, {6.7, 0.6}, {8.0, 0.7}, {9.3, 0.8}, {10.7, 0.9}, {11.5, 1.05}, {13.0, 1.2}, {14.5, 1.3}
    };
    private static final int[] AGES = {0, 2, 4, 6, 9, 12, 18, 24, 36, 48, 60};
    private static final int[] HEIGHTS = {45, 50, 55, 60, 65, 70, 75, 80, 86, 92, 98};

    public static double calculateZScore(String type, double x, double value, String sex) {
        double[][] ref;
        int[] refX;
        switch (type) {
            case "WFA":
                ref = sex.equals("girl") ? WFA_GIRL : WFA_BOY;
                refX = AGES;
                break;
            case "HFA":
                ref = sex.equals("girl") ? HFA_GIRL : HFA_BOY;
                refX = AGES;
                break;
            case "WFH":
                ref = sex.equals("girl") ? WFH_GIRL : WFH_BOY;
                refX = HEIGHTS;
                break;
            default:
                return 0;
        }
        // Interpolate median and SD
        double[] stats = interpolate(refX, ref, x);
        double median = stats[0];
        double sd = stats[1];
        return (value - median) / sd;
    }

    private static double[] interpolate(int[] xArr, double[][] ref, double x) {
        if (x <= xArr[0]) return ref[0];
        if (x >= xArr[xArr.length - 1]) return ref[ref.length - 1];
        for (int i = 0; i < xArr.length - 1; i++) {
            if (x >= xArr[i] && x <= xArr[i + 1]) {
                double ratio = (x - xArr[i]) / (xArr[i + 1] - xArr[i]);
                double median = ref[i][0] + ratio * (ref[i + 1][0] - ref[i][0]);
                double sd = ref[i][1] + ratio * (ref[i + 1][1] - ref[i][1]);
                return new double[]{median, sd};
            }
        }
        return ref[0]; // fallback
    }
} 